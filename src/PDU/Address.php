<?php

namespace PE\Component\SMPP\PDU;

final class Address
{
    public const TON_UNKNOWN           = 0b00000000;
    public const TON_INTERNATIONAL     = 0b00000001;
    public const TON_NATIONAL          = 0b00000010;
    public const TON_NETWORK_SPECIFIC  = 0b00000011;
    public const TON_SUBSCRIBER_NUMBER = 0b00000100;
    public const TON_ALPHANUMERIC      = 0b00000101;
    public const TON_ABBREVIATED       = 0b00000110;

    public const NPI_UNKNOWN       = 0b00000000;
    public const NPI_ISDN          = 0b00000001;
    public const NPI_DATA          = 0b00000011;
    public const NPI_TELEX         = 0b00000100;
    public const NPI_LAND_MOBILE   = 0b00000110;
    public const NPI_NATIONAL      = 0b00001000;
    public const NPI_PRIVATE       = 0b00001001;
    public const NPI_ERMES         = 0b00001010;
    public const NPI_INTERNET_IP   = 0b00001110;
    public const NPI_WAP_CLIENT_ID = 0b00010010;

    /**
     * Type of Number
     * @var int
     */
    private int $ton;

    /**
     * Numbering Plan Identification
     * @var int
     */
    private int $npi;

    /**
     * @var string
     */
    private string $value;

    public function __construct(int $ton, int $npi, string $value)
    {
        $this->ton   = $ton;
        $this->npi   = $npi;
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
