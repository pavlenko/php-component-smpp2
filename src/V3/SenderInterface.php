<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Exception\InvalidPDUException;
use PE\Component\SMPP\Exception\TimeoutException;
use PE\Component\SMPP\PDU\Address;

interface SenderInterface
{
    /**
     * Send SMS
     *
     * @param Address $from
     * @param Address $to
     * @param string  $message
     * @return string
     * @throws InvalidPDUException
     * @throws TimeoutException
     */
    public function send(Address $from, Address $to, string $message): string;
}
