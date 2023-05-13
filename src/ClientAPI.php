<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Deferred;
use PE\Component\SMPP\DTO\PDU;

final class ClientAPI
{
    private Client4 $client;

    public function __construct(Client4 $client)
    {
        $this->client = $client;
    }

    public function submitSM(array $params): Deferred
    {
        return $this->client->send(0/*PDU::ID_SUBMIT_SM*/, $params);
    }

    public function dataSM(array $params): Deferred
    {
        return $this->client->send(PDU::ID_DATA_SM, $params);
    }

    public function querySM(array $params): Deferred
    {
        return $this->client->send(PDU::ID_QUERY_SM, $params);
    }

    public function cancelSM(array $params): Deferred
    {
        return $this->client->send(PDU::ID_CANCEL_SM, $params);
    }

    public function replaceSM(array $params): Deferred
    {
        return $this->client->send(PDU::ID_REPLACE_SM, $params);
    }
}
