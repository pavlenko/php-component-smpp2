<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\PDU;

class Request4
{
    private PDU $pdu;
    private ?int $expectPDU;
    private ?int $expiredAt;

    public function __construct(PDU $pdu, int $expectPDU = null, int $timeout = null)
    {
        $this->pdu       = $pdu;//TODO maybe not need to store full object, just sequence num
        $this->expectPDU = $expectPDU;
        $this->expiredAt = $timeout > 0 ? time() + $timeout : null;
    }

    public function getExpectPDU(): ?int
    {
        return $this->expectPDU;
    }

    public function getExpiredAt(): ?int
    {
        return $this->expiredAt;
    }
}
