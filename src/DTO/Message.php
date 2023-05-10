<?php

namespace PE\Component\SMPP\DTO;

final class Message
{
    public const STATUS_ENROUTE       = 1;
    public const STATUS_DELIVERED     = 2;
    public const STATUS_EXPIRED       = 3;
    public const STATUS_DELETED       = 4;
    public const STATUS_UNDELIVERABLE = 5;
    public const STATUS_ACCEPTED      = 6;
    public const STATUS_UNKNOWN       = 7;
    public const STATUS_REJECTED      = 8;

    private string $id;
    private int $status;

    public function __construct(string $id, int $status)
    {
        $this->id     = $id;
        $this->status = $status;
    }

    public function getID(): string
    {
        return $this->id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }
}
