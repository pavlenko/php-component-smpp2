<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Util\Stream;

class Sender implements SenderInterface
{
    private string $addr;
    private ConnectionInterface $conn;
    private SessionInterface $sess;

    public function __construct(string $addr, SessionInterface $sess)
    {
        $this->addr = $addr;
        $this->sess = $sess;
    }

    private function connect()
    {
        // Bind
        $sequenceNum = $this->sess->newSequenceNum();

        $this->conn = new Connection(Stream::createClient($this->addr));
        $this->conn->sendPDU(new PDU(PDUInterface::ID_BIND_TRANSMITTER, PDUInterface::STATUS_NO_ERROR, $sequenceNum, [
            'system_id'         => $this->sess->getSystemID(),
            'password'          => $this->sess->getPassword(),
            'system_type'       => '',
            'interface_version' => 0x34,
            'address'           => $this->sess->getAddress(),
        ]));

        if (PDUInterface::STATUS_NO_ERROR !== $this->conn->waitPDU($sequenceNum)->getStatus()) {
            throw new \UnexpectedValueException('Unexpected bind response');
        }
    }

    public function sendSMS(SMSInterface $message): string
    {
        $sequenceNum = $this->sess->newSequenceNum();

        $this->conn->sendPDU(new PDU(PDUInterface::ID_SUBMIT_SM, PDUInterface::STATUS_NO_ERROR, $sequenceNum, [
            'short_message'          => $message->getMessage(),
            'dest_address'           => $message->getRecipient(),
            'source_address'         => $message->getSender() ?: $this->sess->getAddress(),
            'data_coding'            => $message->getDataCoding(),
            'schedule_delivery_time' => $message->getScheduleAt(),
            'registered_delivery'    => $message->hasRegisteredDelivery(),
        ]));

        $response = $this->conn->waitPDU($sequenceNum);
        if (PDUInterface::STATUS_NO_ERROR !== $response->getStatus()) {
            throw new \UnexpectedValueException('Error', $response->getStatus());
        }

        return $response->get('message_id');
    }
}
