<?php

namespace PE\Component\SMPP\DTO;

final class TLV implements TLVInterface
{
    private int $tag;
    private string $value;

    public function __construct(int $tag, string $value)
    {
        $this->tag = $tag;
        $this->value = $value;
    }

    public function getTag(): int
    {
        return $this->tag;
    }

    public function getLength(): int
    {
        return strlen($this->value);
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
