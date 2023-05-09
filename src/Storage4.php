<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\PDU;

final class Storage4 implements StorageInterface
{
    /**
     * @var PDU[]
     */
    private array $data = [];

    public function select(Address $address = null): ?PDU
    {
        foreach ($this->data as $pdu) {
            $destination = $pdu->get('dest_address');
            if ($destination instanceof Address && !$address || ($destination->getValue() === $address->getValue())) {
                return $pdu;
            }
        }
        return null;
    }

    public function insert(PDU $pdu): void
    {
        $this->data[spl_object_hash($pdu)] = $pdu;
    }

    public function delete(PDU $pdu): void
    {
        unset($this->data[spl_object_hash($pdu)]);
    }
}
