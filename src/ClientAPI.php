<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Deferred;
use PE\Component\SMPP\DTO\PDU;

class ClientAPI
{
    private Client4 $client;

    public function __construct(Client4 $client)
    {
        $this->client = $client;
    }

    public function submitSM(array $params): Deferred
    {
        //TODO required params check
        return $this->client->send(new PDU(PDU::ID_SUBMIT_SM, 0, 0, $params));
    }

    public function dataSM(PDU $pdu): Deferred
    {
        return $this->client->send($pdu);
    }

    public function querySM(PDU $pdu): Deferred
    {
        return $this->client->send($pdu);
    }

    public function cancelSM(PDU $pdu): Deferred
    {
        return $this->client->send($pdu);
    }

    public function replaceSM(PDU $pdu): Deferred
    {
        return $this->client->send($pdu);
    }
}
