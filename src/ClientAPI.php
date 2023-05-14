<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Deferred;
use PE\Component\SMPP\DTO\Message;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Exception\InvalidArgumentException;

final class ClientAPI
{
    private Client4 $client;

    public function __construct(Client4 $client)
    {
        $this->client = $client;
    }

    public function submitSM(Message $message, array $params = []): Deferred
    {
        if (empty($message->getTargetAddress()) || empty($message->getBody())) {
            throw new InvalidArgumentException('Message body and target address required for SUBMIT_SM');
        }

        $params = [
            PDU::KEY_SRC_ADDRESS        => $message->getSourceAddress(),
            PDU::KEY_DST_ADDRESS        => $message->getTargetAddress(),
            PDU::KEY_SCHEDULED_AT       => $message->getScheduledAt(),
            PDU::KEY_VALIDITY_PERIOD    => $message->getExpiredAt(),
            PDU::KEY_DATA_CODING        => $message->getDataCoding(),
            PDU::KEY_SM_DEFAULT_MSG_ID  => null,
            PDU::KEY_SM_LENGTH          => strlen($message->getBody()),
            PDU::KEY_SHORT_MESSAGE      => $message->getBody(),
        ] + $params;

        return $this->client->send(PDU::ID_SUBMIT_SM, $params);
    }

    public function dataSM(Message $message, array $params): Deferred
    {
        if (empty($message->getSourceAddress()) || empty($message->getTargetAddress())) {
            throw new InvalidArgumentException('Message body and target address required for SUBMIT_SM');
        }

        //TODO where is data???
        $params = [
            PDU::KEY_SRC_ADDRESS => $message->getSourceAddress(),
            PDU::KEY_DST_ADDRESS => $message->getTargetAddress(),
            PDU::KEY_DATA_CODING => $message->getDataCoding(),
        ] + $params;

        return $this->client->send(PDU::ID_DATA_SM, $params);
    }

    public function querySM(Message $message): Deferred
    {
        if (empty($message->getID())) {
            throw new InvalidArgumentException('Message ID required for QUERY_SM');
        }

        return $this->client->send(PDU::ID_QUERY_SM, [
            PDU::KEY_MESSAGE_ID  => $message->getID(),
            PDU::KEY_SRC_ADDRESS => $message->getSourceAddress(),
        ]);
    }

    public function cancelSM(Message $message): Deferred
    {
        if (empty($message->getID()) && empty($message->getSourceAddress())) {
            throw new InvalidArgumentException('Message ID or source address required for CANCEL_SM');
        }

        return $this->client->send(PDU::ID_CANCEL_SM, [
            PDU::KEY_MESSAGE_ID  => $message->getID(),
            PDU::KEY_SRC_ADDRESS => $message->getSourceAddress(),
            PDU::KEY_DST_ADDRESS => $message->getTargetAddress(),
        ]);
    }

    public function replaceSM(Message $message, int $regDelivery = null): Deferred
    {
        if (empty($message->getID()) && empty($message->getSourceAddress())) {
            throw new InvalidArgumentException('Message ID or source address required for CANCEL_SM');
        }

        return $this->client->send(PDU::ID_REPLACE_SM, [
            PDU::KEY_MESSAGE_ID        => $message->getID(),
            PDU::KEY_SRC_ADDRESS       => $message->getSourceAddress(),
            PDU::KEY_SCHEDULED_AT      => $message->getScheduledAt(),
            PDU::KEY_VALIDITY_PERIOD   => $message->getExpiredAt(),
            PDU::KEY_REG_DELIVERY      => $regDelivery,
            PDU::KEY_SM_DEFAULT_MSG_ID => null,
            PDU::KEY_SM_LENGTH         => strlen($message->getBody()),
            PDU::KEY_SHORT_MESSAGE     => $message->getBody(),
        ]);
    }
}
