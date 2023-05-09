<?php

namespace PE\Component\SMPP;

use PE\Component\Loop\Loop;
use PE\Component\Loop\LoopInterface;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\Socket\SelectInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class Client4
{
    private SessionInterface $session;
    private StorageInterface $storage;
    private Factory4 $factory;
    private SelectInterface $select;
    private LoggerInterface $logger;

    private Connection4 $connection;
    private LoopInterface $loop;

    public function __construct(
        SessionInterface $session,
        StorageInterface $storage,
        Factory4 $factory
    ) {
        $this->session = $session;
        $this->storage = $storage;
        $this->factory = $factory;
        $this->select  = $factory->getSocketSelect();
        $this->logger  = $factory->getLogger();

        //TODO pass to constructor
        $this->loop = new Loop(1, function () {
            $this->select->dispatch();
            $this->processTimeout($this->connection);
            $this->processEnquire($this->connection);
            $this->processPending($this->connection);
        });
    }

    public function bind(string $address): void
    {
        $this->logger->log(LogLevel::DEBUG, "Connecting to $address ...");

        $this->connection = $this->factory->createConnection($address);
        $this->connection->setInputHandler(fn(PDU $pdu) => $this->processReceive($this->connection, $pdu));
        $this->connection->setCloseHandler(function () {
            $this->logger->log(LogLevel::DEBUG, "Connection to {$this->connection->getRemoteAddress()} closed");
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

        if (array_key_exists(~PDU::ID_GENERIC_NACK & $pdu->getID(), ConnectionInterface::BOUND_MAP)) {
            $this->logger->log(LogLevel::DEBUG, "Connecting to {$connection->getRemoteAddress()} OK");
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

            //TODO Test reply via external event
//            $this->loop->addSingularTimer(5, function () use ($connection) {
//                $sequenceNum = $this->session->newSequenceNum();
//                $this->connection->send(new PDU(PDU::ID_SUBMIT_SM, PDU::STATUS_NO_ERROR, $sequenceNum, [
//                    'short_message'          => 'WELCOME',
//                    'dest_address'           => new Address(1, 1, '10001112233'),
//                    'source_address'         => $this->session->getAddress(),
//                    'data_coding'            => PDU::DATA_CODING_DEFAULT,
//                    'schedule_delivery_time' => null,
//                    'registered_delivery'    => false,
//                ]));
//                $this->connection->wait(5, $sequenceNum, PDU::ID_SUBMIT_SM_RESP);
//            });
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
