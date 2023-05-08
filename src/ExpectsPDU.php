<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\PDU;

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

    public function toLogger(): string
    {
        return sprintf(
            'ExpectsPDU(%s, %d)',
            implode('|', array_map(fn($id) => PDU::getIdentifiers()[$id] ?? sprintf('0x%08X', $id), $this->expectPDU)),
            $this->seqNum
        );
    }
}
