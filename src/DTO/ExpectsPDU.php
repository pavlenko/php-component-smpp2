<?php

namespace PE\Component\SMPP\DTO;

final class ExpectsPDU
{
    private int $expiredAt;
    private int $seqNum;
    private array $expectPDU;

    private \Closure $resolvedHandler;
    private \Closure $rejectedHandler;

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

    public function then(callable $handler): self
    {
        $this->resolvedHandler = \Closure::fromCallable($handler);
        return $this;
    }

    public function else(callable $handler): self
    {
        $this->rejectedHandler = \Closure::fromCallable($handler);
        return $this;
    }

    public function success(...$arguments): void
    {
        call_user_func($this->resolvedHandler, ...$arguments);
    }

    public function failure(...$arguments): void
    {
        call_user_func($this->rejectedHandler, ...$arguments);
    }
}
