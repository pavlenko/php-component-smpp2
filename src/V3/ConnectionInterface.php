<?php

namespace PE\Component\SMPP\V3;

// lazy stream wrapper
// read/send/wait PDU
use PE\Component\SMPP\PDU\Address;

interface ConnectionInterface
{
    /**
     * Open new stream connection as server or as client (maybe need some child class or pass argument as type)
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
     * Interface version always is 0x34
     */
    public function bind(int $type, string $systemID, string $password = null, Address $address = null): void;

    public function readPDU(): PDUInterface;
    public function sendPDU(PDUInterface $pdu);
    public function waitPDU(): PDUInterface;

    /**
     * Send UNBIND command and close stream connection
     */
    public function exit(): void;
}
