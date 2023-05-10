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

    private Address $sourceAddress;
    private Address $targetAddress;
    private string $message;
    private array $params;
    private ?string $messageID = null;
    private int $status = self::STATUS_ENROUTE;
    private int $errorCode = 0;
    private ?\DateTimeInterface $deliveredAt = null;

    public function __construct(Address $source, Address $target, string $message, array $params = [])
    {
        $this->sourceAddress = $source;
        $this->targetAddress = $target;
        $this->message = $message;
        $this->params = $params;
    }

    public function getSourceAddress(): Address
    {
        return $this->sourceAddress;
    }

    public function getTargetAddress(): Address
    {
        return $this->targetAddress;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function getMessageID(): ?string
    {
        return $this->messageID;
    }

    public function setMessageID(string $messageID): void
    {
        $this->messageID = $messageID;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function setErrorCode(int $errorCode): void
    {
        $this->errorCode = $errorCode;
    }

    public function getDeliveredAt(): ?\DateTimeInterface
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(\DateTimeInterface $deliveredAt): void
    {
        $this->deliveredAt = $deliveredAt;
    }
}
