<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\PDU\PDU;

final class Packet
{
    private PDU $pdu;
    private ?int $expectedResp;
    private ?int $expectedTime;

    public function __construct(PDU $pdu, int $expectedResp = null, int $expectedTime = null)
    {
        $this->pdu          = $pdu;
        $this->expectedResp = $expectedResp;
        $this->expectedTime = $expectedTime;
    }

    public function getPDU(): PDU
    {
        return $this->pdu;
    }

    public function getExpectedResp(): ?int
    {
        return $this->expectedResp;
    }

    public function getExpectedTime(): ?int
    {
        return $this->expectedTime;
    }
}
