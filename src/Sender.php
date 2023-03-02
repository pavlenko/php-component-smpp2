<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\V3\PDU;
use PE\Component\SMPP\V3\PDUInterface;

final class Sender implements SenderInterface
{
    use ClientTrait;

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
