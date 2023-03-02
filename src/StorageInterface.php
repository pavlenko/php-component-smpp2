<?php

namespace PE\Component\SMPP;

// Store outbound PDUs
// add/del support by key (systemID + sequenceNum)
interface StorageInterface
{
    public function select(int $systemID): array;

    public function insert(int $systemID, PDUInterface $pdu): void;

    public function delete(int $systemID, PDUInterface $pdu): void;
}
