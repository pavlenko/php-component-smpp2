<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Exception\ConnectionException;
use PE\Component\SMPP\Exception\InvalidPDUException;
use PE\Component\SMPP\PDU\Address;

interface ConnectionInterface
{
    public const INTERFACE_VER = 0x34;

    public const STATUS_CREATED   = 0x0000;
    public const STATUS_OPENED    = 0x0001;
    public const STATUS_BOUND_TX  = 0x0010;
    public const STATUS_BOUND_RX  = 0x0100;
    public const STATUS_BOUND_TRX = 0x0110;
    public const STATUS_CLOSED    = 0x1000;

    /**
     * Open new stream connection as server or as client (maybe need some child class or pass argument as type)
     *
     * @throws ConnectionException
     */
    public function open(): void;

    /**
     * Send BIND_* command
     *
     * @param int          $type     Bind type: transmitter/transceiver/receiver
     * @param string       $systemID System ID, can be used as a username
     * @param string|null  $password Password, for authentication
     * @param Address|null $address  Default sender address
     *
     * @throws ConnectionException
     * @throws InvalidPDUException
     */
    public function bind(int $type, string $systemID, string $password = null, Address $address = null): void;

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
     * @param int $commandID
     * @param int $seqNum
     * @param PDUInterface|null $pdu
     *
     * @throws ConnectionException
     */
    public function sendPDU(int $commandID, int $seqNum, PDUInterface $pdu): void;

    /**
     * Wait PDU by specific type & sequence number
     *
     * @param int $commandID
     * @param int $seqNum
     * @param float $timeout
     *
     * @return PDUInterface
     */
    public function waitPDU(int $commandID, int $seqNum, float $timeout = 0): PDUInterface;

    /**
     * Send UNBIND command and close stream connection
     */
    public function exit(): void;
}
