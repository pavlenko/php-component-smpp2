<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Exception\InvalidPDUException;
use PE\Component\SMPP\Exception\TimeoutException;
use PE\Component\SMPP\PDU\Address;

interface SenderInterface
{
    /**
     * Send a SMS
     *
     * @param Address      $recipient Recipient address
     * @param string       $message   SMS Message
     * @param Address|null $sender    Sender address, this override default one
     * @return string
     * @throws InvalidPDUException
     * @throws TimeoutException
     */
    public function send(Address $recipient, string $message, Address $sender = null): string;
}
