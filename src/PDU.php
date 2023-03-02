<?php

namespace PE\Component\SMPP;

final class PDU implements PDUInterface
{
    private int $id;
    private int $status;
    private int $seqNum;
    private array $params;

    public function __construct(int $id, int $status, int $seqNum, array $params = [])
    {
        $this->id     = $id;
        $this->status = $status;
        $this->seqNum = $seqNum;
        $this->params = $params;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getSeqNum(): int
    {
        return $this->seqNum;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->params);
    }

    public function get(string $name, $default = null)
    {
        return $this->has($name) ? $this->params[$name] : $default;
    }

    public function set(string $name, $value): void
    {
        $this->params[$name] = $value;
    }
}
