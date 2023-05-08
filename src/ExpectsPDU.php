<?php

namespace PE\Component\SMPP;

final class ExpectsPDU
{
    private ?int $expiredAt;
    private ?int $expectPDU;

    public function __construct(int $timeout, int $expectPDU = null)
    {
        $this->expiredAt = time() + $timeout;
        $this->expectPDU = $expectPDU;//TODO array of integers
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
