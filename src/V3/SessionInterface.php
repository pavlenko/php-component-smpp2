<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\PDU\Address;

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
     * Get new sequence num for send PDU
     *
     * @return int
     */
    public function newSequenceNum(): int;
}
