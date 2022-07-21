<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Util\Stream;

class Sender implements SenderInterface
{
    private string $address;
    private ConnectionInterface $connection;
    private SessionInterface $session;

    public function __construct(string $address, SessionInterface $session)
    {
        $this->address = $address;
        $this->session = $session;
    }

    public function connect(int $type): void
    {
        $sequenceNum = $this->session->newSequenceNum();

        $this->connection = new Connection(Stream::createClient($this->address));
        $this->connection->sendPDU(new PDU($type, PDUInterface::STATUS_NO_ERROR, $sequenceNum, [
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

    public function sendSMS(SMSInterface $message): string
    {
        $sequenceNum = $this->session->newSequenceNum();

        $this->connection->sendPDU(new PDU(PDUInterface::ID_SUBMIT_SM, PDUInterface::STATUS_NO_ERROR, $sequenceNum, [
            'short_message'          => $message->getMessage(),
            'dest_address'           => $message->getRecipient(),
            'source_address'         => $message->getSender() ?: $this->session->getAddress(),
            'data_coding'            => $message->getDataCoding(),
            'schedule_delivery_time' => $message->getScheduleAt(),
            'registered_delivery'    => $message->hasRegisteredDelivery(),
        ]));

        $response = $this->connection->waitPDU($sequenceNum);
        if (PDUInterface::STATUS_NO_ERROR !== $response->getStatus()) {
            throw new \UnexpectedValueException('Error', $response->getStatus());
        }

        return $response->get('message_id');
    }
}
