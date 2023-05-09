<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Util\EventsInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

/* @deprecated */
trait ClientTrait
{
    private string $address;
    private FactoryInterface $factory;
    private SessionInterface $session;
    private EventsInterface $events;
    private LoggerInterface $logger;
    private ConnectionInterface $connection;

    public function __construct(string $address, FactoryInterface $factory, SessionInterface $session, EventsInterface $events, LoggerInterface $logger = null)
    {
        $this->address = $address;
        $this->factory = $factory;
        $this->session = $session;
        $this->events  = $events;
        $this->logger  = $logger ?: new NullLogger();
    }

    public function bind(): void
    {
        $sequenceNum = $this->session->newSequenceNum();

        $this->logger->log(LogLevel::INFO, "SMPP Client of ($this->address) connecting ...");

        $this->connection = $this->factory->createClientConnection($this->address, $this->logger);
        $this->connection->sendPDU(new PDU(PDU::ID_BIND_TRANSMITTER, PDU::STATUS_NO_ERROR, $sequenceNum, [
            'system_id'         => $this->session->getSystemID(),
            'password'          => $this->session->getPassword(),
            'system_type'       => '',
            'interface_version' => ConnectionInterface::INTERFACE_VER,
            'address'           => $this->session->getAddress(),
        ]));

        $response = $this->connection->waitPDU($sequenceNum);
        if (PDU::STATUS_NO_ERROR !== $response->getStatus()) {
            throw new \UnexpectedValueException('Error', $response->getStatus());
        }

        $this->logger->log(LogLevel::INFO, "SMPP Client of ($this->address) connected");
    }

    public function exit(): void
    {
        $sequenceNum = $this->session->newSequenceNum();

        $this->connection->sendPDU(new PDU(PDU::ID_UNBIND, PDU::STATUS_NO_ERROR, $sequenceNum));

        $response = $this->connection->waitPDU($sequenceNum);
        if (PDU::STATUS_NO_ERROR !== $response->getStatus()) {
            $this->logger->log(LogLevel::WARNING, 'UNBIND failed: ' . $response->getStatus());
        }

        $this->connection->exit();
    }
}
