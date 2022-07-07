<?php

namespace PE\SMPP;

use PE\SMPP\PDU\Address;

final class Session
{
    public const MODE_TRANSMITTER = 1;
    public const MODE_RECEIVER    = 2;
    public const MODE_TRANSCEIVER = 3;

    private int $mode;
    private string $systemType;
    private string $systemID;
    private string $password;
    private int $interfaceVer = 0;
    private Address $address;

    public function __construct(
        int $mode,
        string $systemID,
        string $password = '',
        string $systemType = '',
        ?Address $address = null,
        int $interfaceVer = null
    ) {
        $modes = [self::MODE_TRANSMITTER, self::MODE_RECEIVER, self::MODE_TRANSCEIVER];
        if (!in_array($mode, $modes)) {
            throw new \InvalidArgumentException('Mode must be of ' . self::class . '::MODE_* constants');
        }

        $this->mode         = $mode;
        $this->systemID     = $systemID;
        $this->password     = $password;
        $this->systemType   = $systemType;
        $this->address      = $address ?? new Address(Address::TON_UNKNOWN, Address::NPI_UNKNOWN, '');
        $this->interfaceVer = $interfaceVer ?? 0x34;
    }

    public function getMode(): int
    {
        return $this->mode;
    }

    public function getSystemType(): string
    {
        return $this->systemType;
    }

    public function getSystemID(): string
    {
        return $this->systemID;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getInterfaceVer(): int
    {
        return $this->interfaceVer;
    }

    public function getAddress(): Address
    {
        return $this->address;
    }
}
