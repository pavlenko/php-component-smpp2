<?php

namespace PE\Component\SMPP\V3;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait ClientTrait
{
    private string $address;
    private FactoryInterface $factory;
    private SessionInterface $session;
    private LoggerInterface $logger;
    private ConnectionInterface $connection;

    public function __construct(string $address, FactoryInterface $factory, SessionInterface $session, LoggerInterface $logger = null)
    {
        $this->address = $address;
        $this->factory = $factory;
        $this->session = $session;
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
            'interface_version' => 0x34,
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
            throw new \UnexpectedValueException('Error', $response->getStatus());//maybe bot need
        }

        $this->connection->exit();
    }
}
