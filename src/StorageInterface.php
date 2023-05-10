<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\Message;
use PE\Component\SMPP\DTO\PDU;

interface StorageInterface
{
    public function search(string $messageID, Address $src = null): ?Message;

    public function select(Address $address = null): ?PDU;

    public function insert(PDU $pdu): void;

    public function update(Message $message): void;

    public function delete(PDU $pdu): void;
}
