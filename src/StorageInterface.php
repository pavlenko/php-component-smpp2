<?php

namespace PE\Component\SMPP;

// Store outbound PDUs
// add/del support by key (systemID + sequenceNum)
use PE\Component\SMPP\V3\PDUInterface;

interface StorageInterface
{
    public function select(int $systemID): array;

    public function insert(int $systemID, PDUInterface $pdu): void;

    public function delete(int $systemID, PDUInterface $pdu): void;
}
