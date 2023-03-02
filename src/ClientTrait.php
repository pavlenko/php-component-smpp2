<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\DTO\PDUInterface;
use PE\Component\SMPP\Util\EventsInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

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

        $this->connection = $this->factory->createClientConnection($this->address, $this->logger);
        $this->connection->sendPDU(new PDU(PDUInterface::ID_BIND_TRANSMITTER, PDUInterface::STATUS_NO_ERROR, $sequenceNum, [
            'system_id'         => $this->session->getSystemID(),
            'password'          => $this->session->getPassword(),
            'system_type'       => '',
            'interface_version' => ConnectionInterface::INTERFACE_VER,
            'address'           => $this->session->getAddress(),
        ]));

        $response = $this->connection->waitPDU($sequenceNum);
        if (PDUInterface::STATUS_NO_ERROR !== $response->getStatus()) {
            throw new \UnexpectedValueException('Error', $response->getStatus());
        }
    }

    public function exit(): void
    {
        $sequenceNum = $this->session->newSequenceNum();

        $this->connection->sendPDU(new PDU(PDUInterface::ID_UNBIND, PDUInterface::STATUS_NO_ERROR, $sequenceNum));

        $response = $this->connection->waitPDU($sequenceNum);
        if (PDUInterface::STATUS_NO_ERROR !== $response->getStatus()) {
            $this->logger->log(LogLevel::WARNING, 'UNBIND failed: ' . $response->getStatus());
        }

        $this->connection->exit();
    }
}
