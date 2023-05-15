<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\DTO\TLV;

final class Validator
{
    //TODO check required
    //TODO check optional allowed
    //TODO check optional missing
    public function validate(PDU $pdu): void
    {
        foreach ($pdu->getParams() as $key => $val) {
            if (is_numeric($key)) {
                $this->checkTLV($pdu->getID(), $key, $val);
            }
        }
    }

    private function checkTLV(int $id, int $key, $val)
    {
        if (!array_key_exists($id, PDU::ALLOWED_TLV_BY_ID) || !in_array($key, PDU::ALLOWED_TLV_BY_ID[$id])) {
            throw new ValidatorException(
                sprintf(
                    'Param %s not allowed for PDU %s',
                    TLV::TAG()[$key] ?? sprintf('0x%02X', $key),
                    PDU::getIdentifiers()[$id] ?? sprintf('0x%04X', $id)
                ),
                PDU::STATUS_OPTIONAL_PARAM_NOT_ALLOWED
            );
        }

        if (!$val instanceof TLV) {
            //TODO check value type
            throw new ValidatorException(
                sprintf('Invalid param %s', TLV::TAG()[$key] ?? sprintf('0x%02X', $key)),
                PDU::STATUS_INVALID_OPTIONAL_PARAM_VALUE
            );
        }
    }
}
