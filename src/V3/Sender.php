<?php

namespace PE\Component\SMPP\V3;

class Sender implements SenderInterface
{
    private ConnectionInterface $conn;
    private SessionInterface $sess;

    public function __construct(ConnectionInterface $conn, SessionInterface $sess)
    {
        $this->conn = $conn;
        $this->sess = $sess;
    }

    public function sendSMS(SMSInterface $message): string
    {
        $num = $this->sess->newSequenceNum();
        $pdu = new PDU(PDUInterface::ID_SUBMIT_SM, PDUInterface::STATUS_NO_ERROR, $num, [
            'short_message'          => $message->getMessage(),
            'dest_address'           => $message->getRecipient(),
            'source_address'         => $message->getSender() ?: $this->sess->getAddress(),
            'data_coding'            => $message->getDataCoding(),
            'schedule_delivery_time' => $message->getScheduleAt(),
            'registered_delivery'    => $message->hasRegisteredDelivery(),
        ]);

        $this->conn->sendPDU($pdu);

        $response = $this->conn->waitPDU($num);
        if (PDUInterface::STATUS_NO_ERROR !== $response->getStatus()) {
            throw new \UnexpectedValueException('Error', $response->getStatus());
        }

        return $response->get('message_id');
    }
}
