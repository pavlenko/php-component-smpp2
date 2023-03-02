<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\PDUInterface;
use PE\Component\SMPP\Exception\ConnectionException;
use PE\Component\SMPP\Exception\InvalidPDUException;
use PE\Component\SMPP\Exception\TimeoutException;
use PE\Component\SMPP\Util\Stream;

interface ConnectionInterface
{
    public const INTERFACE_VER = 0x34;

    public const STATUS_CREATED   = 0b0000;
    public const STATUS_OPENED    = 0b0001;
    public const STATUS_BOUND_TX  = 0b0010;
    public const STATUS_BOUND_RX  = 0b0100;
    public const STATUS_BOUND_TRX = 0b0110;
    public const STATUS_CLOSED    = 0b1000;

    public const BOUND_MAP = [
        PDUInterface::ID_BIND_RECEIVER    => self::STATUS_BOUND_TX,
        PDUInterface::ID_BIND_TRANSMITTER => self::STATUS_BOUND_RX,
        PDUInterface::ID_BIND_TRANSCEIVER => self::STATUS_BOUND_TRX,
    ];

    /**
     * Get stream
     *
     * @return Stream
     */
    public function getStream(): Stream;

    /**
     * Get status code
     *
     * @return int
     */
    public function getStatus(): int;

    /**
     * Set status code
     *
     * @param int $status
     */
    public function setStatus(int $status): void;

    /**
     * Read PDU
     *
     * @return PDUInterface|null Returns PDU object on success, null on error (need exception) or no data
     *
     * @throws ConnectionException
     * @throws InvalidPDUException
     */
    public function readPDU(): ?PDUInterface;

    /**
     * Send PDU
     *
     * @param PDUInterface|null $pdu
     *
     * @throws ConnectionException
     */
    public function sendPDU(PDUInterface $pdu): void;

    /**
     * Wait PDU by sequence number (if greater than 0)
     *
     * @param int $seqNum
     * @param float $timeout
     *
     * @return PDUInterface
     *
     * @throws TimeoutException
     */
    public function waitPDU(int $seqNum = 0, float $timeout = 0): PDUInterface;

    /**
     * Send UNBIND command and close stream connection
     */
    public function exit(): void;
}
