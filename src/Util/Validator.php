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
        // Check TLV
        $disallowed = [];
        foreach ($pdu->getParams() as $key => $val) {
            if (is_numeric($key) && $val instanceof TLV && (
                !array_key_exists($pdu->getID(), PDU::ALLOWED_TLV_BY_ID)
                || !in_array($key, PDU::ALLOWED_TLV_BY_ID[$pdu->getID()])
                )) {
                $disallowed[] = TLV::TAG()[$key] ?? sprintf('0x%04X', $key);
            }
        }
        if ($disallowed) {
            throw new ValidatorException(
                'Param(s) not allowed: ' . json_encode($disallowed),
                PDU::STATUS_OPTIONAL_PARAM_NOT_ALLOWED
            );
        }
    }
}
