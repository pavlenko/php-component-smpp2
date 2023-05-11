<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Address;

final class Session implements SessionInterface
{
    private string $systemID;
    private ?string $password;
    private ?Address $address;
    private int $seqNum;
    private int $responseTimeout;
    private int $inactiveTimeout;

    public function __construct(
        string $systemID,
        string $password = null,
        Address $address = null,
        int $responseTimeout = self::DEFAULT_RESPONSE_TIMEOUT,
        int $inactiveTimeout = self::DEFAULT_INACTIVE_TIMEOUT
    ) {
        $this->systemID = $systemID;
        $this->password = $password;
        $this->address  = $address;

        $this->responseTimeout = max(1, $responseTimeout);
        $this->inactiveTimeout = max(1, $inactiveTimeout);

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

    public function getSequenceNum(): int
    {
        return $this->seqNum;
    }

    public function newSequenceNum(): int
    {
        return $this->seqNum++;
    }

    public function getResponseTimeout(): int
    {
        return $this->responseTimeout;
    }

    public function getInactiveTimeout(): int
    {
        return $this->inactiveTimeout;
    }
}
