<?php

namespace PE\SMPP\PDU;

final class Builder
{
    private string $buffer = '';

    public function addInt8(int $value): void
    {
        $this->buffer .= pack('C', $value);
    }

    public function addInt16(int $value): void
    {
        $this->buffer .= pack('n', $value);
    }

    public function addInt32(int $value): void
    {
        $this->buffer .= pack('N', $value);
    }

    public function addBytes(int $value): void
    {
        $this->buffer .= $value;
    }

    public function addString(string $value): void
    {
        $this->buffer .= pack('C', trim($value) . "\0");
    }

    public function addDateTime(?\DateTimeInterface $dateTime): void
    {
        $this->addString($dateTime ? $dateTime->format('ymdHis') . '000+' : '');
    }

    public function addAddress(?Address $value): void
    {
        $this->addInt8($value ? $value->getTon() : 0);
        $this->addInt8($value ? $value->getNpi() : 0);
        $this->addString($value ? $value->getValue() : '');
    }

    public function __toString(): string
    {
        return $this->buffer;
    }
}
