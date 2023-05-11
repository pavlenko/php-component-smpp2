<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Address;

final class Search
{
    private ?string $messageID = null;
    private ?int $status = null;
    private ?Address $sourceAddress = null;
    private ?Address $targetAddress = null;
    private bool $checkSchedule = false;

    public function getMessageID(): ?string
    {
        return $this->messageID;
    }

    public function setMessageID(string $messageID): self
    {
        $this->messageID = $messageID;
        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getSourceAddress(): ?Address
    {
        return $this->sourceAddress;
    }

    public function setSourceAddress(Address $sourceAddress): self
    {
        $this->sourceAddress = $sourceAddress;
        return $this;
    }

    public function getTargetAddress(): ?Address
    {
        return $this->targetAddress;
    }

    public function setTargetAddress(Address $targetAddress): self
    {
        $this->targetAddress = $targetAddress;
        return $this;
    }

    public function isCheckSchedule(): bool
    {
        return $this->checkSchedule;
    }

    public function setCheckSchedule(): self
    {
        $this->checkSchedule = true;
        return $this;
    }
}
