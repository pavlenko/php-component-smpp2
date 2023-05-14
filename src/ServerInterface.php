<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\PDU;

interface ServerInterface
{
    //TODO fill
    public const ALLOWED_ID_FOR_BOUND = [
        PDU::ID_GENERIC_NACK          => [],
        PDU::ID_QUERY_SM              => [],
        PDU::ID_QUERY_SM_RESP         => [],
        PDU::ID_SUBMIT_SM             => [],
        PDU::ID_SUBMIT_SM_RESP        => [],
        PDU::ID_DELIVER_SM            => [],
        PDU::ID_DELIVER_SM_RESP       => [],
        PDU::ID_UNBIND                => [],
        PDU::ID_UNBIND_RESP           => [],
        PDU::ID_REPLACE_SM            => [],
        PDU::ID_REPLACE_SM_RESP       => [],
        PDU::ID_CANCEL_SM             => [],
        PDU::ID_CANCEL_SM_RESP        => [],
        PDU::ID_ENQUIRE_LINK          => [],
        PDU::ID_ENQUIRE_LINK_RESP     => [],
        PDU::ID_SUBMIT_MULTI          => [],
        PDU::ID_SUBMIT_MULTI_RESP     => [],
        PDU::ID_ALERT_NOTIFICATION    => [],
        PDU::ID_DATA_SM               => [],
        PDU::ID_DATA_SM_RESP          => [],
    ];

    /**
     * Listen to socket
     *
     * @param string $address
     * @return void
     */
    public function bind(string $address): void;

    /**
     * Close all sessions & stop server
     */
    public function stop(): void;
}
