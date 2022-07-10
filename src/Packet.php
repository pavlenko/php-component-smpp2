<?php

namespace PE\SMPP;

use PE\SMPP\PDU\PDU;

final class Packet
{
    private string $systemID;
    private PDU $pdu;
    private ?int $expectedResp;
    private ?int $expectedTime;

    public function __construct(string $systemID, PDU $pdu, int $expectedResp = null, int $expectedTime = null)
    {
        $this->systemID     = $systemID;
        $this->pdu          = $pdu;
        $this->expectedResp = $expectedResp;
        $this->expectedTime = $expectedTime;
    }

    public function getSystemID(): string
    {
        return $this->systemID;
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
