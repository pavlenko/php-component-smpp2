<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\Message;
use PE\Component\SMPP\DTO\PDU;

interface StorageInterface
{
    public function search(
        string $messageID = null,
        Address $sourceAddress = null,
        Address $targetAddress = null,
        bool $checkScheduled = false
    ): ?Message;

    public function search2(Search $search): ?Message;

    public function select(Address $address = null): ?PDU;

    public function insert(Message $message): void;

    public function update(Message $message): void;

    public function delete(PDU $pdu): void;
}
