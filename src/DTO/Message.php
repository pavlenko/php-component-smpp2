<?php

namespace PE\Component\SMPP\DTO;

final class Message
{
    public const STATUS_PENDING       = 0;
    public const STATUS_ENROUTE       = 1;
    public const STATUS_DELIVERED     = 2;
    public const STATUS_EXPIRED       = 3;
    public const STATUS_DELETED       = 4;
    public const STATUS_UNDELIVERABLE = 5;
    public const STATUS_ACCEPTED      = 6;
    public const STATUS_UNKNOWN       = 7;
    public const STATUS_REJECTED      = 8;

    private ?Address $sourceAddress = null;
    private ?Address $targetAddress = null;
    private ?string $id = null;
    private ?string $body = null;
    private int $status = self::STATUS_PENDING;
    private int $errorCode = 0;
    private int $dataCoding = PDU::DATA_CODING_DEFAULT;
    private ?DateTime $scheduledAt = null;
    private ?DateTime $deliveredAt = null;
    private ?DateTime $expiredAt = null;
    private array $params = [];

    public function __construct(string $body = null, Address $targetAddress = null, Address $sourceAddress = null)
    {
        $this->setBody($body);
        $this->setTargetAddress($targetAddress);
        $this->setSourceAddress($sourceAddress);
    }

    public function getSourceAddress(): ?Address
    {
        return $this->sourceAddress;
    }

    public function setSourceAddress(?Address $sourceAddress): void
    {
        $this->sourceAddress = $sourceAddress;
    }

    public function getTargetAddress(): ?Address
    {
        return $this->targetAddress;
    }

    public function setTargetAddress(?Address $targetAddress): void
    {
        $this->targetAddress = $targetAddress;
    }

    public function getID(): ?string
    {
        return $this->id;
    }

    public function setID(?string $id): void
    {
        $this->id = $id;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body;
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

    public function getDataCoding(): int
    {
        return $this->dataCoding;
    }

    public function setDataCoding(int $dataCoding): void
    {
        $this->dataCoding = $dataCoding;
    }

    public function getScheduledAt(): ?DateTime
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?DateTime $scheduledAt): void
    {
        $this->scheduledAt = $scheduledAt;
    }

    public function getDeliveredAt(): ?DateTime
    {
        return $this->deliveredAt;
    }

    public function setDeliveredAt(?DateTime $deliveredAt): void
    {
        $this->deliveredAt = $deliveredAt;
    }

    public function getExpiredAt(): ?DateTime
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(?DateTime $expiredAt): void
    {
        $this->expiredAt = $expiredAt;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function setParams(array $params): void
    {
        //TODO check if allowed param
        $this->params = $params;
    }
}
