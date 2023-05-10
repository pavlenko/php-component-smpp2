<?php

namespace PE\Component\SMPP;

use PE\Component\Event\EmitterInterface;
use PE\Component\Event\Event;
use PE\Component\Loop\LoopInterface;
use PE\Component\SMPP\DTO\PDU;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Client4
{
    private SessionInterface $session;
    private StorageInterface $storage;
    private EmitterInterface $emitter;
    private FactoryInterface $factory;
    private LoggerInterface $logger;
    private LoopInterface $loop;

    private ?Connection4 $connection = null;

    public function __construct(
        SessionInterface $session,
        StorageInterface $storage,
        EmitterInterface $emitter,
        FactoryInterface $factory,
        LoggerInterface $logger = null
    ) {
        $this->session = $session;
        $this->storage = $storage;
        $this->emitter = $emitter;
        $this->factory = $factory;
        $this->logger  = $logger ?: new NullLogger();

        $this->loop = $factory->createDispatcher(function () {
            $this->processTimeout($this->connection);
            $this->processEnquire($this->connection);
            $this->processPending($this->connection);
        });
    }

    public function bind(string $address, int $bind): void
    {
        $this->logger->log(LogLevel::DEBUG, "Connecting to $address ...");

        $this->connection = $this->factory->createConnection($this->factory->createSocketClient($address));
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

    public function exit(): void
    {
        if (null === $this->connection) {
            throw new \RuntimeException('You must call bind() before any other operation');
        }

        if (ConnectionInterface::STATUS_CLOSED === $this->connection->getStatus()) {
            $this->logger->log(LogLevel::ERROR, 'Cannot exit on closed connection');
            return;
        }

        $sequenceNum = $this->session->newSequenceNum();
        $this->connection->send(new PDU(PDU::ID_UNBIND, PDU::STATUS_NO_ERROR, $sequenceNum));
        $this->connection->wait(5, $sequenceNum, PDU::ID_UNBIND_RESP);
        //TODO $this->wait();
    }

    //TODO send: submit_sm, data_sm, query_sm, cancel_sm, replace_sm, deliver_sm_resp
    //TODO recv: submit_sm_resp, data_sm, data_sm_resp, query_sm_resp, cancel_sm_resp, replace_sm_resp, deliver_sm

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
            $this->emitter->dispatch(new Event('DELIVER_SM', $connection, $pdu));
        }
    }

    private function processTimeout(Connection4 $connection): void
    {
        $expects = $connection->getExpects();
        foreach ($expects as $expect) {
            if ($expect->getExpiredAt() < time()) {
                $connection->close('Timed out');
            }
        }
    }

    private function processEnquire(Connection4 $connection): void
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
