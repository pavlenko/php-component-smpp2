<?php

namespace PE\SMPP\PDU;

final class TLV
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

    public function getValue(): string
    {
        return $this->value;
    }
}
