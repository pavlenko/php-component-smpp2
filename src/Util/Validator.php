<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\DTO\TLV;

final class Validator implements ValidatorInterface
{
    private ?string $password;
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
            case PDU::ID_SUBMIT_SM:
            case PDU::ID_DELIVER_SM:
                $this->validateServiceType($pdu->get(PDU::KEY_SERVICE_TYPE));
                $this->validateTargetAddress($pdu->get(PDU::KEY_DST_ADDRESS), true);
                $this->validateESMEClass($pdu->get(PDU::KEY_ESM_CLASS));
                $this->validatePriorityFlag($pdu->get(PDU::KEY_PRIORITY_FLAG));
                $this->validateRegDeliveryFlag($pdu->get(PDU::KEY_REG_DELIVERY));
                if (empty($pdu->get(PDU::KEY_SHORT_MESSAGE))) {//TODO validate message
                    throw new ValidatorException('SHORT_MESSAGE required', PDU::STATUS_INVALID_DEFINED_MESSAGE);
                }
                break;
            case PDU::ID_SUBMIT_SM_RESP:
            case PDU::ID_DELIVER_SM_RESP:
            case PDU::ID_DATA_SM_RESP:
            case PDU::ID_QUERY_SM:
                $this->validateMessageID($pdu->get(PDU::KEY_MESSAGE_ID), true);
                $this->validateSourceAddress($pdu->get(PDU::KEY_SRC_ADDRESS), false);
                break;
            case PDU::ID_DATA_SM:
                $this->validateServiceType($pdu->get(PDU::KEY_SERVICE_TYPE));
                $this->validateTargetAddress($pdu->get(PDU::KEY_DST_ADDRESS), true);
                $this->validateESMEClass($pdu->get(PDU::KEY_ESM_CLASS));
                $this->validateRegDeliveryFlag($pdu->get(PDU::KEY_REG_DELIVERY));
                break;
            case PDU::ID_QUERY_SM_RESP:
                $this->validateMessageID($pdu->get(PDU::KEY_MESSAGE_ID), true);
                if (empty($pdu->get(PDU::KEY_MESSAGE_STATE))) {
                    throw new ValidatorException('MESSAGE_STATE required', PDU::STATUS_UNKNOWN_ERROR);
                }
                break;
            case PDU::ID_CANCEL_SM:
                $this->validateServiceType($pdu->get(PDU::KEY_SERVICE_TYPE));
                $this->validateMessageID($pdu->get(PDU::KEY_MESSAGE_ID), false);
                $this->validateSourceAddress($pdu->get(PDU::KEY_SRC_ADDRESS), true);
                $this->validateTargetAddress($pdu->get(PDU::KEY_DST_ADDRESS), empty($pdu->get(PDU::KEY_MESSAGE_ID)));
                break;
            case PDU::ID_REPLACE_SM:
                $this->validateMessageID($pdu->get(PDU::KEY_MESSAGE_ID), true);
                $this->validateSourceAddress($pdu->get(PDU::KEY_SRC_ADDRESS), true);
                $this->validateRegDeliveryFlag($pdu->get(PDU::KEY_REG_DELIVERY));
                if (empty($pdu->get(PDU::KEY_SHORT_MESSAGE))) {//TODO validate message
                    throw new ValidatorException('SHORT_MESSAGE required', PDU::STATUS_INVALID_DEFINED_MESSAGE);
                }
                break;
            case PDU::ID_ALERT_NOTIFICATION:
                $this->validateSourceAddress($pdu->get(PDU::KEY_SRC_ADDRESS), true);
                if (empty($pdu->get(PDU::KEY_ESME_ADDRESS))) {
                    throw new ValidatorException('ESME_ADDRESS required', PDU::STATUS_UNKNOWN_ERROR);
                }
        }

        foreach ($pdu->getParams() as $key => $val) {
            if (is_numeric($key)) {
                $this->checkTLV($pdu->getID(), $key, $val);
            }
        }
    }

    private function validateServiceType($value): void
    {
        if (null !== $value && !is_string($value)) {
            throw new ValidatorException('Invalid SERVICE_TYPE type', PDU::STATUS_INVALID_SERVICE_TYPE);
        }

        $allowed = [
            PDU::SERVICE_TYPE_NONE,
            PDU::SERVICE_TYPE_CMT,
            PDU::SERVICE_TYPE_CPT,
            PDU::SERVICE_TYPE_VMN,
            PDU::SERVICE_TYPE_VMA,
            PDU::SERVICE_TYPE_WAP,
            PDU::SERVICE_TYPE_USSD,
        ];

        if (!in_array($value, $allowed)) {
            throw new ValidatorException('Invalid SERVICE_TYPE value', PDU::STATUS_INVALID_SERVICE_TYPE);
        }
    }

    private function validateESMEClass($value): void
    {
        if (!is_int($value)) {
            throw new ValidatorException('Invalid ESM_CLASS type', PDU::STATUS_INVALID_ESM_CLASS);
        }

        $allowed = [
            PDU::ESM_MSG_TYPE_DEFAULT,
            PDU::ESM_MSG_TYPE_HAS_DELIVERY_RECEIPT,
            PDU::ESM_MSG_TYPE_HAS_ACK_AUTO,
            PDU::ESM_MSG_TYPE_HAS_ACK_MANUAL,
            PDU::ESM_MSG_TYPE_HAS_DELIVERY_NOTIFY,
        ];

        if (!in_array($value & 0b00_11_11_00, $allowed)) {
            throw new ValidatorException('Invalid ESM_CLASS value', PDU::STATUS_INVALID_ESM_CLASS);
        }
    }

    private function validatePriorityFlag($value)
    {
        if (!is_int($value)) {
            throw new ValidatorException('Invalid PRIORITY_FLAG type', PDU::STATUS_INVALID_PRIORITY_FLAG);
        }

        $allowed = [PDU::PRIORITY_DEFAULT, PDU::PRIORITY_HIGH, PDU::PRIORITY_URGENT, PDU::PRIORITY_EMERGENCY];

        if (!in_array($value, $allowed)) {
            throw new ValidatorException('Invalid PRIORITY_FLAG value', PDU::STATUS_INVALID_PRIORITY_FLAG);
        }
    }

    private function validateRegDeliveryFlag($value): void
    {
        if (!is_int($value)) {
            throw new ValidatorException('Invalid REG_DELIVERY type', PDU::STATUS_INVALID_REG_DELIVERY_FLAG);
        }

        $allowed = [
            PDU::REG_DELIVERY_SMSC_NO_DR,
            PDU::REG_DELIVERY_SMSC_DR_REQUESTED,
            PDU::REG_DELIVERY_SMSC_DR_FAIL_ONLY,
        ];

        if (!in_array($value & 0b11_10_00_11, $allowed)) {
            throw new ValidatorException('Invalid REG_DELIVERY value', PDU::STATUS_INVALID_REG_DELIVERY_FLAG);
        }
    }

    private function validateMessageID($messageID, bool $required)
    {
        if (empty($messageID) && $required) {
            throw new ValidatorException('Required', PDU::STATUS_INVALID_MESSAGE_ID);
        }
        if (!empty($messageID) && strlen($messageID) > 64) {
            throw new ValidatorException('Invalid', PDU::STATUS_INVALID_MESSAGE_ID);
        }
    }

    private function validateSourceAddress($address, bool $required)
    {
        if (null !== $address) {
            if (!$address instanceof Address || empty($address->getValue())) {
                throw new ValidatorException('Invalid value', PDU::STATUS_INVALID_SRC_ADDRESS);
            }
            if (!array_key_exists($address->getTON(), Address::TON())) {
                throw new ValidatorException('Invalid TON', PDU::STATUS_INVALID_SRC_TON);
            }
            if (!array_key_exists($address->getNPI(), Address::NPI())) {
                throw new ValidatorException('Invalid NPI', PDU::STATUS_INVALID_SRC_NPI);
            }
        } elseif ($required) {
            throw new ValidatorException('Required', PDU::STATUS_INVALID_SRC_ADDRESS);
        }
    }

    private function validateTargetAddress($address, bool $required)
    {
        if (null !== $address) {
            if (!$address instanceof Address || empty($address->getValue())) {
                throw new ValidatorException('Invalid value', PDU::STATUS_INVALID_DST_ADDRESS);
            }
            if (!array_key_exists($address->getTON(), Address::TON())) {
                throw new ValidatorException('Invalid TON', PDU::STATUS_INVALID_DST_TON);
            }
            if (!array_key_exists($address->getNPI(), Address::NPI())) {
                throw new ValidatorException('Invalid NPI', PDU::STATUS_INVALID_DST_NPI);
            }
        } elseif ($required) {
            throw new ValidatorException('Required', PDU::STATUS_INVALID_DST_ADDRESS);
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
