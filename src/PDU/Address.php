<?php

namespace PE\SMPP\PDU;

final class Address
{
    /**
     * Type of Number
     * @var int
     */
    private $ton;

    /**
     * Numbering Plan Identification
     * @var int
     */
    private $npi;

    /**
     * @var string
     */
    private $value;

    public function __construct(int $ton, int $npi, string $value)
    {
        $this->ton = $ton;
        $this->npi = $npi;
        $this->value = $value;
    }

    public function getTon(): int
    {
        return $this->ton;
    }

    public function getNpi(): int
    {
        return $this->npi;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}
