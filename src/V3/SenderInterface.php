<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\PDU\Address;

interface SenderInterface
{
    public function send(Address $from, Address $to, string $message): void;
}
