<?php

namespace PE\SMPP;

use PE\SMPP\PDU\PDU;

final class Packet
{
    private PDU $pdu;
    private ?int $expectedResp;
    private ?int $expectedTill;

    public function __construct(PDU $pdu, int $expectedResp = null, int $expectedTill = null)
    {
        $this->pdu          = $pdu;
        $this->expectedResp = $expectedResp;
        $this->expectedTill = $expectedTill;
    }

    public function getPdu(): PDU
    {
        return $this->pdu;
    }

    public function getExpectedResp(): ?int
    {
        return $this->expectedResp;
    }

    public function getExpectedTill(): ?int
    {
        return $this->expectedTill;
    }
}
