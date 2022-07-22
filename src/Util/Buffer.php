<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\PDU\Address;
use PE\Component\SMPP\PDU\TLV;

final class Buffer
{
    private string $buffer;
    private int $position = 0;

    public function __construct(string $buffer = '')
    {
        $this->buffer = $buffer;
    }

    public function isEOF(): bool
    {
        return $this->position >= strlen($this->buffer);
    }

    public function bytesLeft(): int
    {
        return strlen($this->buffer) - $this->position;
    }

    public function shiftInt8(): int
    {
        $value = unpack('C', $this->buffer, $this->position)[1];
        $this->position += 1;
        return $value;
    }

    public function writeInt8(?int $value): void
    {
        $this->buffer .= pack('C', $value ?? 0);
    }

    public function shiftInt16(): int
    {
        $value = unpack('n', $this->buffer, $this->position)[1];
        $this->position += 2;
        return $value;
    }

    public function writeInt16(?int $value): void
    {
        $this->buffer .= pack('n', $value ?? 0);
    }

    public function shiftInt32(): int
    {
        $value = unpack('N', $this->buffer, $this->position)[1];
        $this->position += 4;
        return $value;
    }

    public function writeInt32(?int $value): void
    {
        $this->buffer .= pack('N', $value ?? 0);
    }

    public function shiftBytes(int $maxLength): string
    {
        $data = '';
        while (!$this->isEOF() && strlen($data) < $maxLength) {
            $data .= $this->buffer[$this->position];
            $this->position++;
        }

        return $data;
    }

    public function writeBytes(string $value): void
    {
        $this->buffer .= $value;
    }

    public function shiftString(int $maxLength): string
    {
        $value = '';
        while (
            !$this->isEOF()
            && $this->buffer[$this->position] !== "\0"
            && strlen($value) < $maxLength
        ) {
            $value .= $this->buffer[$this->position];
            $this->position++;
        }
        $this->position++;

        return $value;
    }

    public function writeString(?string $value): void
    {
        $this->buffer .= trim($value) . "\0";
    }

    public function shiftTLV(): TLV
    {
        if ((strlen($this->buffer) - $this->position) < 4) {
            throw new \OutOfBoundsException('Cannot shift TLV header');
        }

        $tag    = $this->shiftInt16();
        $length = $this->shiftInt16();

        if ((strlen($this->buffer) - $this->position) < $length) {
            throw new \OutOfBoundsException('Cannot shift TLV value');
        }

        return new TLV($tag, $this->shiftBytes($length));
    }

    public function writeTLV(TLV $tlv): void
    {
        $this->writeInt16($tlv->getTag());
        $this->writeInt16($tlv->getLength());
        $this->writeBytes($tlv->getValue());
    }

    public function shiftDateTime(): \DateTimeInterface
    {
        $dateTime = $this->shiftString(17);
        $dateTime = substr($dateTime, 0, 12);
        return \DateTimeImmutable::createFromFormat('ymdHis', $dateTime);//TODO timezone???
    }

    public function writeDateTime(?\DateTimeInterface $dateTime): void
    {
        $this->writeString($dateTime ? $dateTime->format('ymdHis') . '000+' : '');
    }

    public function shiftAddress(int $maxLength): Address
    {
        return new Address($this->shiftInt8(), $this->shiftInt8(), $this->shiftString($maxLength));
    }

    public function writeAddress(?Address $value): void
    {
        $this->writeInt8($value ? $value->getTon() : 0);
        $this->writeInt8($value ? $value->getNpi() : 0);
        $this->writeString($value ? $value->getValue() : '');
    }

    public function __toString(): string
    {
        return $this->buffer;
    }
}
