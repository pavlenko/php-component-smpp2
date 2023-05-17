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

    private function error(int $errorCode, string $message): void
    {
        throw new ValidatorException($this->pdu->getID(), $errorCode, $message);
    }

    public function validate(PDU $pdu): void
    {
        if ($pdu->getStatus() !== PDU::STATUS_NO_ERROR) {
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
                $this->validateMessage();
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
                $this->validateMessage();
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
                $this->validateMessage();
                break;
            case PDU::ID_ALERT_NOTIFICATION:
                $this->validateSourceAddress(65, true);
                $this->validateAddress(PDU::KEY_ESME_ADDRESS, 65, true);
        }

        $this->validateOptionalParams();
    }

    private function validateString(
        string $key,
        string $value,
        int $min,
        int $max,
        int $code = PDU::STATUS_UNKNOWN_ERROR
    ): void {
        if (strlen($value) < $min) {
            $this->error($code, $key . ' too short');
        }
        if (strlen($value) >= $max) {
            $this->error($code, $key . ' too long');
        }
    }

    private function validateTON(string $key, int $value, int $code = PDU::STATUS_UNKNOWN_ERROR): void
    {
        if (!array_key_exists($value, Address::TON())) {
            $this->error($code, $key . ' invalid TON');
        }
    }

    private function validateNPI(string $key, int $value, int $errorCode = PDU::STATUS_UNKNOWN_ERROR): void
    {
        if (!array_key_exists($value, Address::NPI())) {
            $this->error($errorCode, $key . ' invalid NPI');
        }
    }

    private function validateAddress(string $key, int $max, bool $required): void
    {
        $value = $this->pdu->get($key);
        if ($required && (!$value instanceof Address || empty($value->getValue()))) {
            $this->error(PDU::STATUS_UNKNOWN_ERROR, $key . ' required');
        }
        if ($value instanceof Address) {
            $this->validateString($key, $value->getValue(), 0, $max);
            $this->validateTON($key, $value->getTON());
            $this->validateNPI($key, $value->getNPI());
        }
    }

    private function validateDateTime(string $key, $relativeTo, int $code): void
    {
        $value = $this->pdu->get($key);
        if (!empty($value)) {
            if (!$value instanceof DateTime) {
                $this->error($code, $key . ' invalid type');
            }
            if ($value < $relativeTo) {
                $this->error($code, $key . ' invalid value');
            }
        }
    }

    private function validateSystemID(): void
    {
        $value = $this->pdu->get(PDU::KEY_SYSTEM_ID);
        if (empty($value)) {
            $this->error(PDU::STATUS_INVALID_SYSTEM_ID, PDU::KEY_SYSTEM_ID . ' required');
        }
        if (strlen($value) >= 16) {
            $this->error(PDU::STATUS_INVALID_SYSTEM_ID, PDU::KEY_SYSTEM_ID . ' too long');
        }
    }

    private function validatePassword(): void
    {
        $value = $this->pdu->get(PDU::KEY_PASSWORD);
        if (!empty($value) && strlen($value) >= 9) {
            $this->error(PDU::STATUS_INVALID_PASSWORD, PDU::KEY_PASSWORD . ' too long');
        }
    }

    private function validateSystemType(): void
    {
        $value = $this->pdu->get(PDU::KEY_SYSTEM_TYPE);
        if (!empty($value) && strlen($value) >= 13) {
            $this->error(PDU::STATUS_INVALID_SYSTEM_TYPE, PDU::KEY_SYSTEM_TYPE . ' too long');
        }
    }

    private function validateInterfaceVer(): void
    {
        $value = $this->pdu->get(PDU::KEY_INTERFACE_VERSION);
        if (empty($value)) {
            $this->error(PDU::STATUS_UNKNOWN_ERROR, PDU::KEY_INTERFACE_VERSION . ' required');
        }
        if ($value > ConnectionInterface::INTERFACE_VER) {
            $this->error(PDU::STATUS_UNKNOWN_ERROR, PDU::KEY_INTERFACE_VERSION . ' invalid');
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
            $this->error(PDU::STATUS_INVALID_SERVICE_TYPE, PDU::KEY_SERVICE_TYPE . ' invalid');
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
            $this->error(PDU::STATUS_INVALID_ESM_CLASS, PDU::KEY_ESM_CLASS . ' invalid');
        }
    }

    private function validatePriorityFlag()
    {
        $value   = (int) $this->pdu->get(PDU::KEY_PRIORITY_FLAG);
        $allowed = [PDU::PRIORITY_DEFAULT, PDU::PRIORITY_HIGH, PDU::PRIORITY_URGENT, PDU::PRIORITY_EMERGENCY];

        if (!in_array($value, $allowed)) {
            $this->error(PDU::STATUS_INVALID_PRIORITY_FLAG, PDU::KEY_PRIORITY_FLAG . ' invalid');
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
            $this->error(PDU::STATUS_INVALID_REG_DELIVERY_FLAG, PDU::KEY_REG_DELIVERY . ' invalid');
        }
    }

    private function validateMessageID(bool $required): void
    {
        $value = $this->pdu->get(PDU::KEY_MESSAGE_ID);
        if (empty($value) && $required) {
            $this->error(PDU::STATUS_INVALID_MESSAGE_ID, PDU::KEY_MESSAGE_ID . ' required');
        }
        if (!empty($value) && strlen($value) >= 65) {
            $this->error(PDU::STATUS_INVALID_MESSAGE_ID, PDU::KEY_MESSAGE_ID . ' invalid');
        }
    }

    private function validateMessageStatus(): void
    {
        $value = $this->pdu->get(PDU::KEY_MESSAGE_STATE);
        if (!is_int($value)) {
            $this->error(PDU::STATUS_UNKNOWN_ERROR, PDU::KEY_MESSAGE_STATE . ' invalid type');
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
            $this->error(PDU::STATUS_UNKNOWN_ERROR, PDU::KEY_MESSAGE_STATE . ' invalid value');
        }
    }

    private function validateSourceAddress(int $max, bool $required)
    {
        $value = $this->pdu->get(PDU::KEY_SRC_ADDRESS);
        if ($required && (!$value instanceof Address || empty($value->getValue()))) {
            $this->error(PDU::STATUS_INVALID_SRC_ADDRESS, PDU::KEY_SRC_ADDRESS . ' required');
        }
        if ($value instanceof Address) {
            $this->validateString(PDU::KEY_SRC_ADDRESS, $value->getValue(), 0, $max, PDU::STATUS_INVALID_SRC_ADDRESS);
            $this->validateTON(PDU::KEY_SRC_ADDRESS, $value->getTON(), PDU::STATUS_INVALID_SRC_TON);
            $this->validateNPI(PDU::KEY_SRC_ADDRESS, $value->getNPI(), PDU::STATUS_INVALID_SRC_NPI);
        }
    }

    private function validateTargetAddress(int $max, bool $required)
    {
        $value = $this->pdu->get(PDU::KEY_DST_ADDRESS);
        if ($required && (!$value instanceof Address || empty($value->getValue()))) {
            $this->error(PDU::STATUS_INVALID_DST_ADDRESS, PDU::KEY_DST_ADDRESS . ' required');
        }
        if ($value instanceof Address) {
            $this->validateString(PDU::KEY_DST_ADDRESS, $value->getValue(), 0, $max, PDU::STATUS_INVALID_DST_ADDRESS);
            $this->validateTON(PDU::KEY_DST_ADDRESS, $value->getTON(), PDU::STATUS_INVALID_DST_TON);
            $this->validateNPI(PDU::KEY_DST_ADDRESS, $value->getNPI(), PDU::STATUS_INVALID_DST_NPI);
        }
    }

    private function validateMessage()
    {
        $message = $this->pdu->get(PDU::KEY_SHORT_MESSAGE);
        $length  = $this->pdu->get(PDU::KEY_SM_LENGTH);
        $payload = $this->pdu->get(TLV::TAG_MESSAGE_PAYLOAD);

        if ($length > 0) {
            if ($length > 254) {
                $this->error(PDU::STATUS_INVALID_MESSAGE_LENGTH, ' sm_length invalid');
            }
            if (strlen($message) !== $length) {
                $this->error(PDU::STATUS_INVALID_MESSAGE_LENGTH, ' short_message length not match sm_length');
            }
        } elseif (!$payload instanceof TLV) {
            $this->error(PDU::STATUS_INVALID_DEFINED_MESSAGE, 'Required short_message param or message_payload TLV');
        }
    }

    private function validateOptionalParams(): void
    {
        foreach ($this->pdu->getParams() as $tag => $tlv) {
            if (!is_numeric($tag)) {
                continue;
            }

            if (!array_key_exists($this->pdu->getID(), PDU::ALLOWED_TLV_BY_ID)
                || !in_array($tag, PDU::ALLOWED_TLV_BY_ID[$this->pdu->getID()])) {
                $this->error(PDU::STATUS_OPTIONAL_PARAM_NOT_ALLOWED, sprintf(
                    'TLV %s not allowed for PDU %s',
                    TLV::TAG()[$tag] ?? sprintf('0x%02X', $tag),
                    PDU::getIdentifiers()[$this->pdu->getID()] ?? sprintf('0x%04X', $this->pdu->getID())
                ));
            }

            if (!$tlv instanceof TLV) {
                $this->error(PDU::STATUS_INVALID_OPTIONAL_PARAM_VALUE, sprintf(
                    'TLV %s has invalid type',
                    TLV::TAG()[$tag] ?? sprintf('0x%02X', $tag)
                ));
            }

            switch ($tag) {
                case TLV::TAG_DST_ADDRESS_SUBUNIT:
                case TLV::TAG_DST_NETWORK_TYPE:
                case TLV::TAG_DST_BEARER_TYPE:
                case TLV::TAG_SRC_ADDR_SUBUNIT:
                case TLV::TAG_SRC_NETWORK_TYPE:
                case TLV::TAG_SRC_BEARER_TYPE:
                case TLV::TAG_PAYLOAD_TYPE:
                case TLV::TAG_MS_MSG_WAIT_FACILITIES:
                case TLV::TAG_MS_AVAILABILITY_STATUS:
                case TLV::TAG_MS_VALIDITY:
                case TLV::TAG_PRIVACY_INDICATOR:
                case TLV::TAG_USER_RESPONSE_CODE:
                case TLV::TAG_LANGUAGE_INDICATOR:
                case TLV::TAG_SAR_TOTAL_SEGMENTS:
                case TLV::TAG_SAR_SEGMENT_SEQUENCE_NUM:
                case TLV::TAG_SC_INTERFACE_VERSION:
                case TLV::TAG_CALLBACK_NUM_PRES_IND:
                case TLV::TAG_NUMBER_OF_MESSAGES:
                case TLV::TAG_DPF_RESULT:
                case TLV::TAG_SET_DPF:
                case TLV::TAG_DELIVERY_FAILURE_REASON:
                case TLV::TAG_MORE_MESSAGES_TO_SEND:
                case TLV::TAG_MESSAGE_STATUS:
                case TLV::TAG_USSD_SERVICE_OPERATION:
                case TLV::TAG_DISPLAY_TIME:
                case TLV::TAG_ITS_REPLY_TYPE:
                    if (0 > $tlv->getValue() || $tlv->getValue() > 0xFF) {
                        $this->error(PDU::STATUS_INVALID_OPTIONAL_PARAM_VALUE, TLV::TAG()[$tag] . ' invalid UINT08');
                    }
                    break;
                case TLV::TAG_DESTINATION_PORT:
                case TLV::TAG_DST_TELEMATICS_ID:
                case TLV::TAG_SOURCE_PORT:
                case TLV::TAG_SRC_TELEMATICS_ID:
                case TLV::TAG_USER_MESSAGE_REFERENCE:
                case TLV::TAG_SAR_MSG_REF_NUM:
                case TLV::TAG_SMS_SIGNAL:
                case TLV::TAG_ITS_SESSION_INFO:
                    if (0 > $tlv->getValue() || $tlv->getValue() > 0xFF_FF) {
                        $this->error(PDU::STATUS_INVALID_OPTIONAL_PARAM_VALUE, TLV::TAG()[$tag] . ' invalid UINT16');
                    }
                    break;
                case TLV::TAG_QOS_TIME_TO_LIVE:
                    if (0 > $tlv->getValue() || $tlv->getValue() > 0xFF_FF_FF_FF) {
                        $this->error(PDU::STATUS_INVALID_OPTIONAL_PARAM_VALUE, TLV::TAG()[$tag] . ' invalid UINT32');
                    }
                    break;
                case TLV::TAG_SRC_SUB_ADDRESS:
                case TLV::TAG_DST_SUB_ADDRESS:
                    $this->validateString(TLV::TAG()[$tag], $tlv->getValue(), 1, 23, PDU::STATUS_INVALID_PARAM_LENGTH);
                    break;
                case TLV::TAG_RECEIPTED_MESSAGE_ID:
                case TLV::TAG_CALLBACK_NUM_ATAG:
                    $this->validateString(TLV::TAG()[$tag], $tlv->getValue(), 1, 65, PDU::STATUS_INVALID_PARAM_LENGTH);
                    break;
                case TLV::TAG_ADDITIONAL_STATUS_INFO_TEXT:
                    $this->validateString(TLV::TAG()[$tag], $tlv->getValue(), 1, 256, PDU::STATUS_INVALID_PARAM_LENGTH);
                    break;
                case TLV::TAG_CALLBACK_NUM:
                    $this->validateString(TLV::TAG()[$tag], $tlv->getValue(), 1, 19, PDU::STATUS_INVALID_PARAM_LENGTH);
                    break;
                case TLV::TAG_NETWORK_ERROR_CODE:
                    $this->validateString(TLV::TAG()[$tag], $tlv->getValue(), 3, 3, PDU::STATUS_INVALID_PARAM_LENGTH);
                    break;
                case TLV::TAG_MESSAGE_PAYLOAD:
                    $this->validateString(
                        TLV::TAG()[$tag],
                        $tlv->getValue(),
                        1,
                        65535,
                        PDU::STATUS_INVALID_PARAM_LENGTH
                    );
                    break;
            }
        }
    }
}
