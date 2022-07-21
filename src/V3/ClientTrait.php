<?php

namespace PE\Component\SMPP\V3;

trait ClientTrait
{
    protected string $address;
    protected SessionInterface $session;
    protected FactoryInterface $factory;
    protected ConnectionInterface $connection;

    public function __construct(string $address, SessionInterface $session, FactoryInterface $factory)
    {
        $this->address = $address;
        $this->session = $session;
        $this->factory = $factory;
    }

    public function bind(): void
    {
        $sequenceNum = $this->session->newSequenceNum();

        $this->connection = $this->factory->createClientConnection($this->address);
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
