<?php

namespace PE\Component\SMPP;

use PE\Component\Event\EmitterInterface;
use PE\Component\Loop\Loop;
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
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private Select $select;
    private Connection4 $connection;

    public function __construct(
        SessionInterface $session,
        SerializerInterface $serializer,
        LoggerInterface $logger = null
    ) {
        $this->session = $session;
        $this->serializer = $serializer;
        $this->logger = $logger ?: new NullLogger();
        $this->select = new Select();
    }

    public function bind(string $address): void
    {
        $socket = (new SocketFactory($this->select))->createClient($address);

        // bind start
        $this->logger->log(LogLevel::DEBUG, "Connecting to {$socket->getRemoteAddress()} ...");

        $this->connection = new Connection4($socket, $this->serializer, $this->logger);
        $this->connection->setInputHandler(fn(PDU $pdu) => $this->processReceive($this->connection, $pdu));

        $sequenceNum = $this->session->newSequenceNum();
        $this->connection->send(new PDU(PDU::ID_BIND_RECEIVER, PDU::STATUS_NO_ERROR, $sequenceNum, [
            'system_id'         => $this->session->getSystemID(),
            'password'          => $this->session->getPassword(),
            'system_type'       => '',
            'interface_version' => ConnectionInterface::INTERFACE_VER,
            'address'           => $this->session->getAddress(),
        ]));
        $this->connection->wait(5, $sequenceNum);
        // bind end

        $loop = new Loop(1, fn() => $this->dispatch());

        $socket->setErrorHandler(function ($error) {
            $this->logger->log(LogLevel::ERROR, 'E: ' . $error);
        });

        $socket->setCloseHandler(function ($error = null) use ($loop) {
            $loop->stop();
            $this->logger->log(LogLevel::DEBUG, 'C: ' . ($error ?: 'Closed'));
        });

        $loop->run();
    }

    public function dispatch()
    {
        $this->select->dispatch();
        $this->processTimeout();
        $this->processEnquire();
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

        if (PDU::ID_BIND_TRANSMITTER_RESP === $pdu->getID()) {
            $this->logger->log(LogLevel::DEBUG, "Connecting to {$connection->getClient()->getRemoteAddress()} OK");
        }

        if (PDU::ID_ENQUIRE_LINK === $pdu->getID()) {
            $connection->send(new PDU(PDU::ID_ENQUIRE_LINK_RESP, 0, $pdu->getSeqNum()));
        }

        if (PDU::ID_DELIVER_SM === $pdu->getID()) {
            //TODO
            $connection->send(new PDU(PDU::ID_DELIVER_SM_RESP, 0, $pdu->getSeqNum()));
        }
    }

    private function processTimeout()
    {
        $expects = $this->connection->getExpects();
        foreach ($expects as $expect) {
            if ($expect->getExpiredAt() < time()) {
                $this->connection->close('Timed out');
            }
        }
    }

    private function processEnquire()
    {
        $overdue = time() - $this->connection->getLastMessageTime() > 15;
        if ($overdue) {
            $sequenceNum = $this->session->newSequenceNum();

            $this->connection->send(new PDU(PDU::ID_ENQUIRE_LINK, 0, $sequenceNum));
            $this->connection->wait(5, $sequenceNum, PDU::ID_ENQUIRE_LINK_RESP);
        }
    }
}
