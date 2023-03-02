<?php

namespace PE\Component\SMPP;

final class Session implements SessionInterface
{
    private string $systemID;
    private ?string $password;
    private ?Address $address;
    private int $seqNum;

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

        // Generate random sequence number for make connection more unique
        $this->seqNum = mt_rand(0x001, 0x7FF) << 20;
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

    public function newSequenceNum(): int
    {
        return $this->seqNum++;
    }
}
