<?php

namespace PE\Component\SMPP;

use PE\Component\Loop\Loop;
use PE\Component\Loop\LoopInterface;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\DTO\SMS;
use PE\Component\SMPP\Util\SerializerInterface;
use PE\Component\Socket\Select;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Sender4
{
    private SessionInterface $session;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private Select $select;
    private ?Connection4 $connection = null;
    private LoopInterface $loop;

    public function __construct(
        SessionInterface $session,
        SerializerInterface $serializer,
        LoggerInterface $logger = null
    ) {
        $this->session = $session;
        $this->serializer = $serializer;
        $this->logger = $logger ?: new NullLogger();
        $this->select = new Select();

        $this->loop = new Loop();
        $this->loop->addPeriodicTimer(0.001, function () {
            $this->select->dispatch();
            $this->processTimeout($this->connection);
            if (empty($this->connection->getExpects())) {//TODO check
                $this->loop->stop();
            }
        });
    }

    public function bind(string $address): void
    {
        $factory = new \PE\Component\Socket\Factory($this->select);
        $socket  = $factory->createClient($address);

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
        $this->wait();
    }

    public function send(SMS $message): void
    {
        if (null === $this->connection) {
            throw new \RuntimeException('You must call bind() before any other operation');
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
            $this->logger->log(LogLevel::DEBUG, "Connecting to {$connection->getClient()->getRemoteAddress()} OK");
        }

        if (PDU::ID_UNBIND_RESP === $pdu->getID()) {
            $this->logger->log(LogLevel::DEBUG, "Connecting to {$connection->getClient()->getRemoteAddress()} closed");
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

        $sequenceNum = $this->session->newSequenceNum();
        $this->connection->send(new PDU(PDU::ID_UNBIND, PDU::STATUS_NO_ERROR, $sequenceNum));
        $this->connection->wait(5, $sequenceNum, PDU::ID_UNBIND_RESP);
        $this->wait();
    }
}
