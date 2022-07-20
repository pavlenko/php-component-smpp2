<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\PDU\Address;

class Session implements SessionInterface
{
    private string $systemID;
    private ?string $password;
    private ?Address $address;

    /**
     * @param string $systemID
     * @param string|null $password
     * @param Address|null $address
     */
    public function __construct(string $systemID, string $password = null, Address $address = null)
    {
        $this->systemID = $systemID;
        $this->password = $password;
        $this->address  = $address;
    }

    public function getSystemID(): string
    {
        return $this->systemID;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }
}