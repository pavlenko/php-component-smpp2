<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Address;

interface SessionInterface
{
    public const DEFAULT_RESPONSE_TIMEOUT = 5;
    public const DEFAULT_INACTIVE_TIMEOUT = 60;

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

    /**
     * Get max response timeout in seconds
     *
     * @return int
     */
    public function getResponseTimeout(): int;

    /**
     * Get max inactive timeout in seconds
     *
     * @return int
     */
    public function getInactiveTimeout(): int;
}
