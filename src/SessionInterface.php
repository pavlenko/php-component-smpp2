<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Address;

interface SessionInterface
{
    /**
     * Get system id
     *
     * @return string
     */
    public function getSystemID(): string;

    /**
     * Get password
     *
     * @return string|null
     */
    public function getPassword(): ?string;

    /**
     * Get default address
     *
     * @return Address|null
     */
    public function getAddress(): ?Address;

    /**
     * Get bound mode if any
     *
     * @return int|null
     */
    public function getMode(): ?int;

    /**
     * Get current sequence number
     *
     * @return int
     */
    public function getSequenceNum(): int;

    /**
     * Get new sequence num for send PDU
     *
     * @return int
     */
    public function newSequenceNum(): int;
}
