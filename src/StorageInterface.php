<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\PDU;

// Store outbound PDUs
// add/del support by key (systemID + sequenceNum)
interface StorageInterface
{
    public function select(int $systemID): array;

    public function insert(int $systemID, PDU $pdu): void;

    public function delete(int $systemID, PDU $pdu): void;
}
