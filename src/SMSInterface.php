<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\PDU\Address;

interface SMSInterface
{
    public function getMessage(): string;

    public function getRecipient(): Address;

    public function getSender(): ?Address;

    public function setSender(Address $sender): void;

    public function getDataCoding(): int;

    public function setDataCoding(int $dataCoding): void;

    public function getScheduleAt(): ?\DateTimeImmutable;

    public function setScheduleAt(\DateTimeImmutable $scheduleAt): void;

    public function hasRegisteredDelivery(): bool;

    public function setRegisteredDelivery(bool $flag = true): void;
}
