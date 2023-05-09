<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\DTO\SMS;

/* @deprecated */
final class Sender implements SenderInterface
{
    use ClientTrait;

    public function sendSMS(SMS $message): string
    {
        $sequenceNum = $this->session->newSequenceNum();

        $this->connection->sendPDU(new PDU(PDU::ID_SUBMIT_SM, PDU::STATUS_NO_ERROR, $sequenceNum, [
            'short_message'          => $message->getMessage(),
            'dest_address'           => $message->getRecipient(),
            'source_address'         => $message->getSender() ?: $this->session->getAddress(),
            'data_coding'            => $message->getDataCoding(),
            'schedule_delivery_time' => $message->getScheduleAt(),
            'registered_delivery'    => $message->hasRegisteredDelivery(),
        ]));

        $response = $this->connection->waitPDU($sequenceNum);
        if (PDU::STATUS_NO_ERROR !== $response->getStatus()) {
            throw new \UnexpectedValueException(sprintf('Error code 0x%08X', $response->getStatus()));
        }

        return $response->get('message_id');
    }
}
