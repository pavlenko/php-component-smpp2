<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\ConnectionInterface;
use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\DateTime;
use PE\Component\SMPP\DTO\Message;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\DTO\TLV;
use PE\Component\SMPP\Exception\ValidatorException;

final class Validator implements ValidatorInterface
{
    /**
     * Validation context object
     *
     * @var PDU|null
     */
    private ?PDU $pdu = null;

    private function invalid(int $status, string $message): void
    {
        throw new ValidatorException($this->pdu->getID(), $status, $message);
    }

    public function validate(PDU $pdu): void
    {
        if ($pdu->getStatus() !== PDU::STATUS_NO_ERROR) {
            //TODO maybe validate???
            return;
        }
        $this->pdu = $pdu;
        switch ($pdu->getID()) {
            case PDU::ID_BIND_RECEIVER:
            case PDU::ID_BIND_TRANSMITTER:
            case PDU::ID_BIND_TRANSCEIVER:
                $this->validateSystemID();
                $this->validatePassword();
                $this->validateSystemType();
                $this->validateInterfaceVer();
                $this->validateAddress(PDU::KEY_ADDRESS, 41, false);
                break;
            case PDU::ID_BIND_RECEIVER_RESP:
            case PDU::ID_BIND_TRANSMITTER_RESP:
            case PDU::ID_BIND_TRANSCEIVER_RESP:
                $this->validateSystemID();
                break;
            case PDU::ID_SUBMIT_SM:
            case PDU::ID_DELIVER_SM:
                $this->validateServiceType();
                $this->validateSourceAddress(21, false);
                $this->validateTargetAddress(21, true);
                $this->validateESMEClass();
                $this->validatePriorityFlag();
                $this->validateRegDeliveryFlag();
                if (PDU::ID_SUBMIT_SM === $pdu->getID()) {
                    $this->validateDateTime(PDU::KEY_SCHEDULED_AT, null, PDU::STATUS_INVALID_SCHEDULED_AT);
                    $this->validateDateTime(
                        PDU::KEY_VALIDITY_PERIOD,
                        $pdu->get(PDU::KEY_SCHEDULED_AT),
                        PDU::STATUS_INVALID_EXPIRED_AT
                    );
                }
                $this->validateMessage(
                    $pdu->get(PDU::KEY_SM_LENGTH),
                    $pdu->get(PDU::KEY_SHORT_MESSAGE),
                    $pdu->get(TLV::TAG_MESSAGE_PAYLOAD)
                );
                break;
            case PDU::ID_SUBMIT_SM_RESP:
            case PDU::ID_DELIVER_SM_RESP:
            case PDU::ID_DATA_SM_RESP:
            case PDU::ID_QUERY_SM:
                $this->validateMessageID(true);
                $this->validateSourceAddress(21, false);
                break;
            case PDU::ID_DATA_SM:
                $this->validateServiceType();
                $this->validateSourceAddress(21, true);
                $this->validateTargetAddress(21, true);
                $this->validateESMEClass();
                $this->validateRegDeliveryFlag();
                $this->validateMessage(0, null, $pdu->get(TLV::TAG_MESSAGE_PAYLOAD));
                break;
            case PDU::ID_QUERY_SM_RESP:
                $this->validateMessageID(true);
                $this->validateMessageStatus();
                break;
            case PDU::ID_CANCEL_SM:
                $this->validateServiceType();
                $this->validateMessageID(false);
                $this->validateSourceAddress(21, true);
                $this->validateTargetAddress(21, empty($pdu->get(PDU::KEY_MESSAGE_ID)));
                break;
            case PDU::ID_REPLACE_SM:
                $this->validateMessageID(true);
                $this->validateSourceAddress(21, true);
                $this->validateRegDeliveryFlag();
                $this->validateDateTime(PDU::KEY_SCHEDULED_AT, null, PDU::STATUS_INVALID_SCHEDULED_AT);
                $this->validateDateTime(
                    PDU::KEY_VALIDITY_PERIOD,
                    $pdu->get(PDU::KEY_SCHEDULED_AT),
                    PDU::STATUS_INVALID_EXPIRED_AT
                );
                $this->validateMessage(
                    $pdu->get(PDU::KEY_SM_LENGTH),
                    $pdu->get(PDU::KEY_SHORT_MESSAGE),
                    $pdu->get(TLV::TAG_MESSAGE_PAYLOAD)
                );
                break;
            case PDU::ID_ALERT_NOTIFICATION:
                $this->validateSourceAddress(65, true);
                $this->validateAddress(PDU::KEY_ESME_ADDRESS, 65, true);
        }

        foreach ($pdu->getParams() as $key => $val) {
            if (is_numeric($key)) {
                $this->checkTLV($pdu->getID(), $key, $val);
            }
        }
    }

    private function validateString(string $key, string $value, int $max, int $code = PDU::STATUS_UNKNOWN_ERROR): void
    {
        if (strlen($value) >= $max) {
            $this->invalid($code, $key . ' too long');
        }
    }

    private function validateTON(string $key, int $value, int $code = PDU::STATUS_UNKNOWN_ERROR): void
    {
        if (!array_key_exists($value, Address::TON())) {
            $this->invalid($code, $key . ' invalid TON');
        }
    }

    private function validateNPI(string $key, int $value, int $errorCode = PDU::STATUS_UNKNOWN_ERROR): void
    {
        if (!array_key_exists($value, Address::NPI())) {
            $this->invalid($errorCode, $key . ' invalid NPI');
        }
    }

    private function validateAddress(string $key, int $max, bool $required): void
    {
        $value = $this->pdu->get($key);
        if ($required && (!$value instanceof Address || empty($value->getValue()))) {
            $this->invalid(PDU::STATUS_UNKNOWN_ERROR, $key . ' required');
        }
        if ($value instanceof Address) {
            $this->validateString($key, $value->getValue(), $max);
            $this->validateTON($key, $value->getTON());
            $this->validateNPI($key, $value->getNPI());
        }
    }

    private function validateDateTime(string $key, $relativeTo, int $code): void
    {
        $value = $this->pdu->get($key);
        if (!empty($value)) {
            if (!$value instanceof DateTime) {
                $this->invalid($code, $key . ' invalid type');
            }
            if ($value < $relativeTo) {
                $this->invalid($code, $key . ' invalid value');
            }
        }
    }

    private function validateSystemID(): void
    {
        $value = $this->pdu->get(PDU::KEY_SYSTEM_ID);
        if (empty($value)) {
            $this->invalid(PDU::STATUS_INVALID_SYSTEM_ID, PDU::KEY_SYSTEM_ID . ' required');
        }
        if (strlen($value) >= 16) {
            $this->invalid(PDU::STATUS_INVALID_SYSTEM_ID, PDU::KEY_SYSTEM_ID . ' too long');
        }
    }

    private function validatePassword(): void
    {
        $value = $this->pdu->get(PDU::KEY_PASSWORD);
        if (!empty($value) && strlen($value) >= 9) {
            $this->invalid(PDU::STATUS_INVALID_PASSWORD, PDU::KEY_PASSWORD . ' too long');
        }
    }

    private function validateSystemType(): void
    {
        $value = $this->pdu->get(PDU::KEY_SYSTEM_TYPE);
        if (!empty($value) && strlen($value) >= 13) {
            $this->invalid(PDU::STATUS_INVALID_SYSTEM_TYPE, PDU::KEY_SYSTEM_TYPE . ' too long');
        }
    }

    private function validateInterfaceVer(): void
    {
        $value = $this->pdu->get(PDU::KEY_INTERFACE_VERSION);
        if (empty($value)) {
            $this->invalid(PDU::STATUS_UNKNOWN_ERROR, PDU::KEY_INTERFACE_VERSION . ' required');
        }
        if ($value > ConnectionInterface::INTERFACE_VER) {
            $this->invalid(PDU::STATUS_UNKNOWN_ERROR, PDU::KEY_INTERFACE_VERSION . ' invalid');
        }
    }

    private function validateServiceType(): void
    {
        $value = $this->pdu->get(PDU::KEY_SERVICE_TYPE);

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
            $this->invalid(PDU::STATUS_INVALID_SERVICE_TYPE, PDU::KEY_SERVICE_TYPE . ' invalid');
        }
    }

    private function validateESMEClass(): void
    {
        $value = (int) $this->pdu->get(PDU::KEY_ESM_CLASS);
        $allowed = [
            PDU::ESM_MSG_TYPE_DEFAULT,
            PDU::ESM_MSG_TYPE_HAS_DELIVERY_RECEIPT,
            PDU::ESM_MSG_TYPE_HAS_ACK_AUTO,
            PDU::ESM_MSG_TYPE_HAS_ACK_MANUAL,
            PDU::ESM_MSG_TYPE_HAS_DELIVERY_NOTIFY,
        ];

        if (!in_array($value & 0b00_11_11_00, $allowed)) {
            $this->invalid(PDU::STATUS_INVALID_ESM_CLASS, PDU::KEY_ESM_CLASS . ' invalid');
        }
    }

    private function validatePriorityFlag()
    {
        $value   = (int) $this->pdu->get(PDU::KEY_PRIORITY_FLAG);
        $allowed = [PDU::PRIORITY_DEFAULT, PDU::PRIORITY_HIGH, PDU::PRIORITY_URGENT, PDU::PRIORITY_EMERGENCY];

        if (!in_array($value, $allowed)) {
            $this->invalid(PDU::STATUS_INVALID_PRIORITY_FLAG, PDU::KEY_PRIORITY_FLAG . ' invalid');
        }
    }

    private function validateRegDeliveryFlag(): void
    {
        $value   = (int) $this->pdu->get(PDU::KEY_REG_DELIVERY);
        $allowed = [
            PDU::REG_DELIVERY_SMSC_NO_DR,
            PDU::REG_DELIVERY_SMSC_DR_REQUESTED,
            PDU::REG_DELIVERY_SMSC_DR_FAIL_ONLY,
        ];

        if (!in_array($value & 0b11_10_00_11, $allowed)) {
            $this->invalid(PDU::STATUS_INVALID_REG_DELIVERY_FLAG, PDU::KEY_REG_DELIVERY . ' invalid');
        }
    }

    private function validateMessageID(bool $required): void
    {
        $value = $this->pdu->get(PDU::KEY_MESSAGE_ID);
        if (empty($value) && $required) {
            $this->invalid(PDU::STATUS_INVALID_MESSAGE_ID, PDU::KEY_MESSAGE_ID . ' required');
        }
        if (!empty($value) && strlen($value) >= 65) {
            $this->invalid(PDU::STATUS_INVALID_MESSAGE_ID, PDU::KEY_MESSAGE_ID . ' invalid');
        }
    }

    private function validateMessageStatus(): void
    {
        $value = $this->pdu->get(PDU::KEY_MESSAGE_STATE);
        if (!is_int($value)) {
            $this->invalid(PDU::STATUS_UNKNOWN_ERROR, PDU::KEY_MESSAGE_STATE . ' invalid type');
        }

        $allowed = [
            Message::STATUS_PENDING,
            Message::STATUS_ENROUTE,
            Message::STATUS_DELIVERED,
            Message::STATUS_EXPIRED,
            Message::STATUS_DELETED,
            Message::STATUS_UNDELIVERABLE,
            Message::STATUS_ACCEPTED,
            Message::STATUS_UNKNOWN,
            Message::STATUS_REJECTED,
        ];

        if (!in_array($value, $allowed)) {
            $this->invalid(PDU::STATUS_UNKNOWN_ERROR, PDU::KEY_MESSAGE_STATE . ' invalid value');
        }
    }

    private function validateSourceAddress(int $max, bool $required)
    {
        $value = $this->pdu->get(PDU::KEY_SRC_ADDRESS);
        if ($required && (!$value instanceof Address || empty($value->getValue()))) {
            $this->invalid(PDU::STATUS_INVALID_SRC_ADDRESS, PDU::KEY_SRC_ADDRESS . ' required');
        }
        if ($value instanceof Address) {
            $this->validateString(PDU::KEY_SRC_ADDRESS, $value->getValue(), $max, PDU::STATUS_INVALID_SRC_ADDRESS);
            $this->validateTON(PDU::KEY_SRC_ADDRESS, $value->getTON(), PDU::STATUS_INVALID_SRC_TON);
            $this->validateNPI(PDU::KEY_SRC_ADDRESS, $value->getNPI(), PDU::STATUS_INVALID_SRC_NPI);
        }
    }

    private function validateTargetAddress(int $max, bool $required)
    {
        $value = $this->pdu->get(PDU::KEY_DST_ADDRESS);
        if ($required && (!$value instanceof Address || empty($value->getValue()))) {
            $this->invalid(PDU::STATUS_INVALID_DST_ADDRESS, PDU::KEY_DST_ADDRESS . ' required');
        }
        if ($value instanceof Address) {
            $this->validateString(PDU::KEY_DST_ADDRESS, $value->getValue(), $max, PDU::STATUS_INVALID_DST_ADDRESS);
            $this->validateTON(PDU::KEY_DST_ADDRESS, $value->getTON(), PDU::STATUS_INVALID_DST_TON);
            $this->validateNPI(PDU::KEY_DST_ADDRESS, $value->getNPI(), PDU::STATUS_INVALID_DST_NPI);
        }
    }

    private function validateMessage($length, $message, $payload)
    {
        $message = $this->pdu->get(PDU::KEY_SHORT_MESSAGE);
        $length  = $this->pdu->get(PDU::KEY_SM_LENGTH);
        $payload = $this->pdu->get(TLV::TAG_MESSAGE_PAYLOAD);

        //TODO

        if (!empty($message)) {
            if (0 === $length || strlen($message) !== $length) {
                throw new ValidatorException('Invalid SM_LENGTH value', PDU::STATUS_INVALID_MESSAGE_LENGTH);
            }
        } elseif (0 !== $length) {
            throw new ValidatorException('Invalid SM_LENGTH value', PDU::STATUS_INVALID_MESSAGE_LENGTH);
        } elseif (empty($payload)) {
            throw new ValidatorException(
                'Required SHORT_MESSAGE param or MESSAGE_PAYLOAD TLV',
                PDU::STATUS_INVALID_DEFINED_MESSAGE
            );
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
