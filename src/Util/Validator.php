<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\DTO\TLV;

final class Validator implements ValidatorInterface
{
    private ?string $password = null;
    private array $systemTypes = [];

    public function __construct(?string $password)
    {
        $this->password = $password;
    }

    public function validate(PDU $pdu): void
    {
        switch ($pdu->getID()) {
            case PDU::ID_BIND_RECEIVER:
            case PDU::ID_BIND_TRANSMITTER:
            case PDU::ID_BIND_TRANSCEIVER:
                if (empty($pdu->get(PDU::KEY_SYSTEM_ID))) {
                    throw new ValidatorException('SYSTEM_ID required', PDU::STATUS_INVALID_SYSTEM_ID);
                }
                if ($this->password && $pdu->get(PDU::KEY_PASSWORD) !== $this->password) {
                    throw new ValidatorException('PASSWORD mismatch', PDU::STATUS_INVALID_PASSWORD);
                }
                if ($this->systemTypes && !in_array($pdu->get(PDU::KEY_SYSTEM_TYPE), $this->systemTypes)) {
                    throw new ValidatorException('SYSTEM_TYPE not allowed', PDU::STATUS_INVALID_PASSWORD);
                }
                if (empty($pdu->get(PDU::KEY_INTERFACE_VERSION))) {
                    throw new ValidatorException('INTERFACE_VERSION required', PDU::STATUS_UNKNOWN_ERROR);
                }
                break;
            case PDU::ID_BIND_RECEIVER_RESP:
            case PDU::ID_BIND_TRANSMITTER_RESP:
            case PDU::ID_BIND_TRANSCEIVER_RESP:
                if (empty($pdu->get(PDU::KEY_SYSTEM_ID))) {
                    throw new ValidatorException('SYSTEM_ID required', PDU::STATUS_INVALID_SYSTEM_ID);
                }
                break;
                //TODO others
        }

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
