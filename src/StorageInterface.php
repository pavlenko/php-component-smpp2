<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\PDU;

interface StorageInterface
{
    public function select(Address $address = null): ?PDU;

    public function insert(PDU $pdu): void;

    public function delete(PDU $pdu): void;
}
