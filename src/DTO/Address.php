<?php

namespace PE\Component\SMPP\DTO;

/**
 * @codeCoverageIgnore
 */
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

    public function getTON(): int
    {
        return $this->ton;
    }

    public function getNPI(): int
    {
        return $this->npi;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public static function TON(): array
    {
        $constants = (new \ReflectionClass(__CLASS__))->getConstants();
        $constants = array_filter($constants, fn($name) => 0 === strpos($name, 'TON_'), ARRAY_FILTER_USE_KEY);
        $constants = array_flip($constants);
        return array_map(fn($name) => substr($name, 4), $constants);
    }

    public static function NPI(): array
    {
        $constants = (new \ReflectionClass(__CLASS__))->getConstants();
        $constants = array_filter($constants, fn($name) => 0 === strpos($name, 'NPI_'), ARRAY_FILTER_USE_KEY);
        $constants = array_flip($constants);
        return array_map(fn($name) => substr($name, 4), $constants);
    }

    public function dump(): string
    {
        return sprintf(
            'Address(ton: %s, npi: %s, val: "%s")',
            self::TON()[$this->ton] ?? sprintf('0b%08b', $this->ton),
            self::NPI()[$this->npi] ?? sprintf('0b%08b', $this->npi),
            $this->value
        );
    }
}
