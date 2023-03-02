<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\PDU\Address;

final class SMS implements SMSInterface
{
    private string $message;
    private Address $recipient;
    private ?Address $sender = null;
    private int $dataCoding = PDUInterface::DATA_CODING_DEFAULT;
    private ?\DateTimeImmutable $scheduleAt = null;
    private bool $registeredDelivery = false;

    public function __construct(string $message, Address $recipient)
    {
        $this->message   = $message;
        $this->recipient = $recipient;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getRecipient(): Address
    {
        return $this->recipient;
    }

    public function getSender(): ?Address
    {
        return $this->sender;
    }

    public function setSender(Address $sender): void
    {
        $this->sender = $sender;
    }

    public function getDataCoding(): int
    {
        return $this->dataCoding;
    }

    public function setDataCoding(int $dataCoding): void
    {
        $this->dataCoding = $dataCoding;
    }

    public function getScheduleAt(): ?\DateTimeImmutable
    {
        return $this->scheduleAt;
    }

    public function setScheduleAt(\DateTimeImmutable $scheduleAt): void
    {
        $this->scheduleAt = $scheduleAt;
    }

    public function hasRegisteredDelivery(): bool
    {
        return $this->registeredDelivery;
    }

    public function setRegisteredDelivery(bool $flag = true): void
    {
        $this->registeredDelivery = $flag;
    }
}
