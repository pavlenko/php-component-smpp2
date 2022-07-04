<?php

namespace PE\SMPP;

use PE\SMPP\PDU\Address;
use PE\SMPP\PDU\TLV;

final class Decoder
{
    private string $buffer;
    private int $position = 0;

    public function isEOF(): bool
    {
        return $this->position >= strlen($this->buffer);
    }

    public function __construct(string $buffer)
    {
        $this->buffer = $buffer;
    }

    public function readInt8(): int
    {
        $value = unpack('C', $this->buffer, $this->position)[1];
        $this->position += 1;
        return $value;
    }

    public function readInt16(): int
    {
        $value = unpack('n', $this->buffer, $this->position)[1];
        $this->position += 2;
        return $value;
    }

    public function readInt32(): int
    {
        $value = unpack('N', $this->buffer, $this->position)[1];
        $this->position += 4;
        return $value;
    }

    public function readBytes(int $maxLength): string
    {
        $data = '';
        while (!$this->isEOF() && strlen($data) < $maxLength) {
            $data .= $this->buffer[$this->position];
            $this->position++;
        }

        return $data;
    }

    public function readString(int $maxLength): string
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

    public function readTLV(): TLV
    {
        if ((strlen($this->buffer) - $this->position) < 4) {
            throw new \OutOfBoundsException('Cannot read TLV header');
        }

        $tag    = $this->readInt16();
        $length = $this->readInt16();

        if ((strlen($this->buffer) - $this->position) < $length) {
            throw new \OutOfBoundsException('Cannot read TLV value');
        }

        return new TLV($tag, $this->readBytes($length));
    }

    public function readDateTime(): \DateTimeInterface
    {
        $dateTime = $this->readString(17);
        $dateTime = substr($dateTime, 0, 12);
        return \DateTimeImmutable::createFromFormat('ymdHis', $dateTime);//TODO timezone???
    }

    public function readAddress(int $maxLength = 21): Address
    {
        return new Address($this->readInt8(), $this->readInt8(), $this->readString($maxLength));
    }
}
