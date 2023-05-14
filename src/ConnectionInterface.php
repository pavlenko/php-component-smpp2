<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Deferred;
use PE\Component\SMPP\DTO\PDU;

interface ConnectionInterface
{
    public const INTERFACE_VER = 0x34;

    public const STATUS_OPENED    = 0b000;
    public const STATUS_BOUND_TX  = 0b001;
    public const STATUS_BOUND_RX  = 0b010;
    public const STATUS_BOUND_TRX = 0b011;
    public const STATUS_CLOSED    = 0b100;

    public const BIND_MAP = [
        PDU::ID_BIND_RECEIVER    => self::STATUS_BOUND_TX,
        PDU::ID_BIND_TRANSMITTER => self::STATUS_BOUND_RX,
        PDU::ID_BIND_TRANSCEIVER => self::STATUS_BOUND_TRX,
    ];

    public const BOUND_MAP = [
        PDU::ID_BIND_RECEIVER_RESP    => self::STATUS_BOUND_TX,
        PDU::ID_BIND_TRANSMITTER_RESP => self::STATUS_BOUND_RX,
        PDU::ID_BIND_TRANSCEIVER_RESP => self::STATUS_BOUND_TRX,
    ];

    /**
     * Set input handler
     *
     * @param callable $handler
     */
    public function setInputHandler(callable $handler): void;

    /**
     * Set error handler
     *
     * @param callable $handler
     */
    public function setErrorHandler(callable $handler): void;

    /**
     * Set close handler
     *
     * @param callable $handler
     */
    public function setCloseHandler(callable $handler): void;

    /**
     * Get cached client address
     *
     * @return string|null
     */
    public function getClientAddress(): ?string;

    /**
     * Get cached remote address
     *
     * @return string|null
     */
    public function getRemoteAddress(): ?string;

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus(): int;

    /**
     * Set status
     *
     * @param int $status
     */
    public function setStatus(int $status): void;

    /**
     * Get bound session
     *
     * @return SessionInterface|null
     */
    public function getSession(): ?SessionInterface;

    /**
     * Set bound session
     *
     * @param SessionInterface $session
     */
    public function setSession(SessionInterface $session): void;

    /**
     * Get last incoming/outgoing message timestamp
     *
     * @return int
     */
    public function getLastMessageTime(): int;

    /**
     * Set last incoming/outgoing message timestamp to now
     *
     * @return void
     */
    public function updLastMessageTime(): void;

    /**
     * Wait for incoming PDU based on expected sequence number and/or expected PDU identifiers
     *
     * @param int $timeout
     * @param int $seqNum
     * @param int ...$expectPDU
     * @return Deferred
     */
    public function wait(int $timeout, int $seqNum = 0, int ...$expectPDU): Deferred;

    /**
     * Send PDU
     *
     * @param PDU $pdu
     */
    public function send(PDU $pdu): void;

    /**
     * Close connection with optional reason message
     *
     * @param string|null $message
     */
    public function close(string $message = null): void;
}
