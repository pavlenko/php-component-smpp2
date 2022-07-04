<?php

namespace PE\SMPP\PDU;

use PE\SMPP\Builder;

abstract class PDU
{
    private int $commandStatus = 0;
    private int $sequenceNumber = 1;
    private string $body;

    public function __construct(string $body = '')
    {
        $this->body = $body;
    }

    abstract public function getCommandID(): int;

    public function getCommandLength(): int
    {
        return 16 + strlen($this->getBody());
    }

    public function getCommandStatus(): int
    {
        return $this->commandStatus;
    }

    public function setCommandStatus(int $status): void
    {
        $this->commandStatus = $status;
    }

    public function getSequenceNumber(): int
    {
        return $this->sequenceNumber;
    }

    public function setSequenceNumber(int $sequenceNumber): void
    {
        $this->sequenceNumber = $sequenceNumber;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function __toString(): string
    {
        $builder = new Builder();
        $builder->addInt32($this->getCommandLength());
        $builder->addInt32($this->getCommandID());
        $builder->addInt32($this->getCommandStatus());
        $builder->addInt32($this->getSequenceNumber());

        return $builder . $this->getBody();
    }
}
