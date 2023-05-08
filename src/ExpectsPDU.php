<?php

namespace PE\Component\SMPP;

final class ExpectsPDU
{
    private int $expiredAt;
    private int $seqNum;
    private array $expectPDU;

    public function __construct(int $timeout, int $seqNum, int ...$expectPDU)
    {
        $this->expiredAt = time() + $timeout;
        $this->seqNum    = $seqNum;
        $this->expectPDU = $expectPDU;
    }

    public function getExpiredAt(): ?int
    {
        return $this->expiredAt;
    }

    public function getSeqNum(): int
    {
        return $this->seqNum;
    }

    public function isExpectPDU(int $id): bool
    {
        return in_array($id, $this->expectPDU);
    }
}
