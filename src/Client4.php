<?php

namespace PE\Component\SMPP;

use PE\Component\Loop\Loop;
use PE\Component\Loop\LoopInterface;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Util\SerializerInterface;
use PE\Component\Socket\Factory as SocketFactory;
use PE\Component\Socket\Select;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Client4
{
    private SessionInterface $session;
    private StorageInterface $storage;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private Select $select;
    private Connection4 $connection;
    private LoopInterface $loop;

    public function __construct(
        SessionInterface $session,
        SerializerInterface $serializer,
        LoggerInterface $logger = null
    ) {
        $this->session = $session;
        $this->storage = new Storage4();
        $this->serializer = $serializer;
        $this->logger = $logger ?: new NullLogger();
        $this->select = new Select();

        $this->loop = new Loop(1, function () {
            $this->select->dispatch();
            $this->processTimeout($this->connection);
            $this->processEnquire($this->connection);
            $this->processPending($this->connection);
        });
    }

    public function bind(string $address): void
    {
        $socket = (new SocketFactory($this->select))->createClient($address);

        $this->logger->log(LogLevel::DEBUG, "Connecting to {$socket->getRemoteAddress()} ...");

        $this->connection = new Connection4($socket, $this->serializer, $this->logger);
        $this->connection->setInputHandler(fn(PDU $pdu) => $this->processReceive($this->connection, $pdu));
        $this->connection->setCloseHandler(function () {
            $this->logger->log(
                LogLevel::DEBUG,
                "Connection to {$this->connection->getClient()->getRemoteAddress()} closed"
            );
            $this->loop->stop();
            $this->connection->setStatus(ConnectionInterface::STATUS_CLOSED);
        });

        $sequenceNum = $this->session->newSequenceNum();
        $this->connection->send(new PDU(PDU::ID_BIND_RECEIVER, PDU::STATUS_NO_ERROR, $sequenceNum, [
            'system_id'         => $this->session->getSystemID(),
            'password'          => $this->session->getPassword(),
            'system_type'       => '',
            'interface_version' => ConnectionInterface::INTERFACE_VER,
            'address'           => $this->session->getAddress(),
        ]));
        $this->connection->wait(5, $sequenceNum, PDU::ID_BIND_RECEIVER_RESP);
        // bind end

        //$loop = new Loop(1, fn() => $this->dispatch());

//        $socket->setErrorHandler(function ($error) {
//            $this->logger->log(LogLevel::ERROR, 'E: ' . $error);
//        });

//        $socket->setCloseHandler(function ($error = null) use ($loop) {
//            //$loop->stop();
//            $this->logger->log(LogLevel::DEBUG, 'C: ' . ($error ?: 'Closed'));
//        });

        $this->loop->run();
    }

    private function processReceive(Connection4 $connection, PDU $pdu): void
    {
        // Remove expects PDU if any (prevents close client connection on timeout)
        $connection->delExpects($pdu->getSeqNum(), $pdu->getID());

        // Check errored response
        if (PDU::STATUS_NO_ERROR !== $pdu->getStatus()) {
            $connection->close('Error [' . $pdu->getStatus() . ']');
            return;
        }

        if (PDU::ID_BIND_RECEIVER_RESP === $pdu->getID()) {
            $this->logger->log(LogLevel::DEBUG, "Connecting to {$connection->getClient()->getRemoteAddress()} OK");
            $this->connection->setStatus(ConnectionInterface::BOUND_MAP[~PDU::ID_GENERIC_NACK & $pdu->getID()]);
        }

        if (PDU::ID_ENQUIRE_LINK === $pdu->getID()) {
            $connection->send(new PDU(PDU::ID_ENQUIRE_LINK_RESP, 0, $pdu->getSeqNum()));
        }

        if (PDU::ID_DELIVER_SM === $pdu->getID()) {
            $this->logger->log(
                LogLevel::DEBUG,
                "SMS from {$pdu->get('source_address')->getValue()}: {$pdu->get('short_message')}"
            );
            $connection->send(new PDU(PDU::ID_DELIVER_SM_RESP, 0, $pdu->getSeqNum()));
        }
    }

    private function processTimeout(Connection4 $connection)
    {
        $expects = $connection->getExpects();
        foreach ($expects as $expect) {
            if ($expect->getExpiredAt() < time()) {
                $connection->close('Timed out');
            }
        }
    }

    private function processEnquire(Connection4 $connection)
    {
        $overdue = time() - $connection->getLastMessageTime() > 15;
        if ($overdue) {
            $sequenceNum = $this->session->newSequenceNum();

            $connection->send(new PDU(PDU::ID_ENQUIRE_LINK, 0, $sequenceNum));
            $connection->wait(5, $sequenceNum, PDU::ID_ENQUIRE_LINK_RESP);
        }
    }

    private function processPending(Connection4 $connection): void
    {
        if (!array_key_exists($connection->getStatus(), ConnectionInterface::BOUND_MAP)) {
            return;
        }

        $pdu = $this->storage->select();
        if ($pdu) {
            $connection->send($pdu);
            $connection->wait(5, $pdu->getSeqNum(), PDU::ID_GENERIC_NACK | $pdu->getID());
            $this->storage->delete($pdu);
        }
    }
}
