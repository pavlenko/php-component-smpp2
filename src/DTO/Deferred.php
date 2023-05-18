<?php

namespace PE\Component\SMPP\DTO;

final class Deferred
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

        $this->resolvedHandler = fn() => null;
        $this->rejectedHandler = fn() => null;
    }

    public function getExpiredAt(): ?int
    {
        return $this->expiredAt;
    }

    public function isExpectPDU(int $sequenceNum, int $id): bool
    {
        return $this->seqNum === $sequenceNum || in_array($id, $this->expectPDU);
    }

    public function dump(): string
    {
        $body = [];
        if (!empty($this->expectPDU)) {
            $body[] = 'id: ' . implode('|', array_map(fn($id) => PDU::getIdentifiers()[$id], $this->expectPDU));
        }
        if (!empty($this->seqNum)) {
            $body[] = 'seq: ' . $this->seqNum;
        }
        return sprintf('Deferred(%s)', implode(', ', $body));
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

    public function success(PDU $pdu): void
    {
        call_user_func($this->resolvedHandler, $pdu);
    }

    //TODO fixed interface
    public function failure(...$arguments): void
    {
        call_user_func($this->rejectedHandler, ...$arguments);
    }
}
