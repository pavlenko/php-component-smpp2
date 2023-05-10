<?php

namespace PE\Component\SMPP;

use PE\Component\Loop\LoopInterface;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\DTO\SMS;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Sender4
{
    private SessionInterface $session;
    private FactoryInterface $factory;
    private LoggerInterface $logger;
    private ?Connection4 $connection = null;
    private LoopInterface $loop;

    public function __construct(
        SessionInterface $session,
        FactoryInterface $factory,
        LoggerInterface $logger = null
    ) {
        $this->session = $session;
        $this->factory = $factory;
        $this->logger = $logger ?: new NullLogger();

        $this->loop = $factory->createDispatcher(function () {
            $this->processTimeout($this->connection);
            if (empty($this->connection->getExpects())) {
                $this->loop->stop();
            }
        });
    }

    public function __destruct()
    {
        if ($this->connection) {
            $this->connection->close();
        }
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
        $this->connection->send(new PDU(PDU::ID_BIND_TRANSMITTER, PDU::STATUS_NO_ERROR, $sequenceNum, [
            'system_id'         => $this->session->getSystemID(),
            'password'          => $this->session->getPassword(),
            'system_type'       => '',
            'interface_version' => ConnectionInterface::INTERFACE_VER,
            'address'           => $this->session->getAddress(),
        ]));
        $this->connection->wait(5, $sequenceNum, PDU::ID_BIND_TRANSMITTER_RESP);
        $this->wait();
    }

    public function send(SMS $message): void
    {
        if (null === $this->connection) {
            throw new \RuntimeException('You must call bind() before any other operation');
        }
        if (ConnectionInterface::STATUS_CLOSED === $this->connection->getStatus()) {
            $this->logger->log(LogLevel::ERROR, 'Cannot send to closed connection');
            return;
        }

        $sequenceNum = $this->session->newSequenceNum();
        $this->connection->send(new PDU(PDU::ID_SUBMIT_SM, PDU::STATUS_NO_ERROR, $sequenceNum, [
            'short_message'          => $message->getMessage(),
            'dest_address'           => $message->getRecipient(),
            'source_address'         => $message->getSender() ?: $this->session->getAddress(),
            'data_coding'            => $message->getDataCoding(),
            'schedule_delivery_time' => $message->getScheduleAt(),
            'registered_delivery'    => $message->hasRegisteredDelivery(),
        ]));
        $this->connection->wait(5, $sequenceNum, PDU::ID_SUBMIT_SM_RESP);
    }

    public function wait(): void
    {
        if (null === $this->connection) {
            throw new \RuntimeException('You must call bind() before any other operation');
        }
        if (ConnectionInterface::STATUS_CLOSED === $this->connection->getStatus()) {
            $this->logger->log(LogLevel::ERROR, 'Cannot wait on closed connection');
            return;
        }

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

        if (PDU::ID_BIND_TRANSMITTER_RESP === $pdu->getID()) {
            $this->logger->log(LogLevel::DEBUG, "Connecting to {$connection->getRemoteAddress()} OK");
            $this->connection->setStatus(ConnectionInterface::STATUS_BOUND_TX);
        }

        if (PDU::ID_UNBIND_RESP === $pdu->getID()) {
            $connection->close('Unbind');
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
        $this->wait();
    }
}
