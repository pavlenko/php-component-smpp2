<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\DTO\TLV;
use PE\Component\SMPP\Exception\InvalidPDUException;
use PE\Component\SMPP\Exception\UnknownPDUException;

final class Encoder
{
    public function encode(PDU $pdu): string
    {
        $head = '';
        $body = '';

        switch ($pdu->getID()) {
            case PDU::ID_GENERIC_NACK:
            case PDU::ID_UNBIND:
            case PDU::ID_UNBIND_RESP:
            case PDU::ID_ENQUIRE_LINK:
            case PDU::ID_ENQUIRE_LINK_RESP:
            case PDU::ID_CANCEL_SM_RESP:
            case PDU::ID_REPLACE_SM_RESP:
                // Has not body just known ID
                break;
            case PDU::ID_BIND_RECEIVER:
            case PDU::ID_BIND_TRANSMITTER:
            case PDU::ID_BIND_TRANSCEIVER:
                $body .= $this->encodeString(true, null, 16, $pdu->get(PDU::KEY_SYSTEM_ID));
                $body .= $this->encodeString(false, null, 9, $pdu->get(PDU::KEY_PASSWORD));
                $body .= $this->encodeString(false, null, 13, $pdu->get(PDU::KEY_SYSTEM_TYPE));
                $body .= $this->encodeUint08(true, $pdu->get(PDU::KEY_INTERFACE_VERSION));
                $body .= $this->encodeAddress(false, 41, $pdu->get(PDU::KEY_ADDRESS));
                break;
            case PDU::ID_BIND_RECEIVER_RESP:
            case PDU::ID_BIND_TRANSMITTER_RESP:
            case PDU::ID_BIND_TRANSCEIVER_RESP:
                $body .= $this->encodeString(true, null, 16, $pdu->get(PDU::KEY_SYSTEM_ID));
                break;
            case PDU::ID_SUBMIT_SM:
                $body .= $this->encodeString(false, null, 6, $pdu->get(PDU::KEY_SERVICE_TYPE));
                $body .= $this->encodeAddress(false, 21, $pdu->get(PDU::KEY_SRC_ADDRESS));
                $body .= $this->encodeAddress(true, 21, $pdu->get(PDU::KEY_DST_ADDRESS));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_ESM_CLASS));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_PROTOCOL_ID));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_PRIORITY_FLAG));
                $body .= $this->encodeDateTime(false, $pdu->get(PDU::KEY_SCHEDULE_DELIVERY_TIME));
                $body .= $this->encodeDateTime(false, $pdu->get(PDU::KEY_VALIDITY_PERIOD));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_REG_DELIVERY));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_REPLACE_IF_PRESENT));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_DATA_CODING));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_SM_DEFAULT_MSG_ID));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_SM_LENGTH));
                $body .= $this->encodeString(true, null, 254, $pdu->get(PDU::KEY_SHORT_MESSAGE));
                break;
            case PDU::ID_DELIVER_SM:
                $body .= $this->encodeString(false, null, 6, $pdu->get(PDU::KEY_SERVICE_TYPE));
                $body .= $this->encodeAddress(false, 21, $pdu->get(PDU::KEY_SRC_ADDRESS));
                $body .= $this->encodeAddress(true, 21, $pdu->get(PDU::KEY_DST_ADDRESS));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_ESM_CLASS));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_PROTOCOL_ID));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_PRIORITY_FLAG));
                $body .= $this->encodeDateTime(false, null);
                $body .= $this->encodeDateTime(false, null);
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_REG_DELIVERY));
                $body .= $this->encodeUint08(false, null);
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_DATA_CODING));
                $body .= $this->encodeUint08(false, null);
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_SM_LENGTH));
                $body .= $this->encodeString(true, null, 254, $pdu->get(PDU::KEY_SHORT_MESSAGE));
                break;
            case PDU::ID_SUBMIT_SM_RESP:
            case PDU::ID_DATA_SM_RESP:
                $body .= $this->encodeString(true, null, 65, $pdu->get(PDU::KEY_MESSAGE_ID));
                break;
            case PDU::ID_DELIVER_SM_RESP:
                $body .= $this->encodeString(false, null, null, null);
                break;
            case PDU::ID_DATA_SM:
                $body .= $this->encodeString(false, null, 6, $pdu->get(PDU::KEY_SERVICE_TYPE));
                $body .= $this->encodeAddress(false, 21, $pdu->get(PDU::KEY_SRC_ADDRESS));
                $body .= $this->encodeAddress(true, 21, $pdu->get(PDU::KEY_DST_ADDRESS));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_ESM_CLASS));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_REG_DELIVERY));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_DATA_CODING));
                break;
            case PDU::ID_QUERY_SM:
                $body .= $this->encodeString(true, null, 65, $pdu->get(PDU::KEY_MESSAGE_ID));
                $body .= $this->encodeAddress(false, 21, $pdu->get(PDU::KEY_SRC_ADDRESS));
                break;
            case PDU::ID_QUERY_SM_RESP:
                $body .= $this->encodeString(true, null, 65, $pdu->get(PDU::KEY_MESSAGE_ID));
                $body .= $this->encodeDateTime(false, $pdu->get(PDU::KEY_FINAL_DATE));
                $body .= $this->encodeUint08(true, $pdu->get(PDU::KEY_MESSAGE_STATE));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_ERROR_CODE));
                break;
            case PDU::ID_CANCEL_SM:
                $body .= $this->encodeString(true, null, 65, $pdu->get(PDU::KEY_MESSAGE_ID));
                $body .= $this->encodeAddress(true, 21, $pdu->get(PDU::KEY_SRC_ADDRESS));
                $body .= $this->encodeAddress(false, 21, $pdu->get(PDU::KEY_DST_ADDRESS));
                break;
            case PDU::ID_REPLACE_SM:
                $body .= $this->encodeString(true, null, 65, $pdu->get(PDU::KEY_MESSAGE_ID));
                $body .= $this->encodeAddress(true, 21, $pdu->get(PDU::KEY_SRC_ADDRESS));
                $body .= $this->encodeDateTime(false, $pdu->get(PDU::KEY_SCHEDULE_DELIVERY_TIME));
                $body .= $this->encodeDateTime(false, $pdu->get(PDU::KEY_VALIDITY_PERIOD));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_REG_DELIVERY));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_SM_DEFAULT_MSG_ID));
                $body .= $this->encodeUint08(false, $pdu->get(PDU::KEY_SM_LENGTH));
                $body .= $this->encodeString(true, null, 254, $pdu->get(PDU::KEY_SHORT_MESSAGE));
                break;
            case PDU::ID_ALERT_NOTIFICATION:
                $body .= $this->encodeAddress(true, 65, $pdu->get(PDU::KEY_SRC_ADDRESS));
                $body .= $this->encodeAddress(true, 65, $pdu->get(PDU::KEY_ESME_ADDRESS));
                break;
            default:
                throw new UnknownPDUException(sprintf('Unknown pdu id: 0x%08X', $pdu->getID()));
        }

        foreach ($pdu->getParams() as $key => $tlv) {
            if (!is_string($key)) {
                $body .= $this->encodeTLV($tlv);
            }
        }

        $head .= $this->encodeUint32(true, strlen($body) + 16);
        $head .= $this->encodeUint32(true, $pdu->getID());
        $head .= $this->encodeUint32(false, $pdu->getStatus());
        $head .= $this->encodeUint32(true, $pdu->getSeqNum());

        return $head . $body;
    }

    private function encodeUint08(bool $required, $value): string
    {
        if (null !== $value) {
            $filtered = filter_var($value, FILTER_VALIDATE_INT);
            if (false === $filtered) {
                throw new InvalidPDUException(
                    'Invalid UINT08 value, got ' . (is_object($value) ? get_class($value) : gettype($value))
                );
            }

            if (0 > $filtered) {
                throw new InvalidPDUException('Invalid UINT32 range, min > ' . $value);
            }

            if (0xFF < $filtered) {
                throw new InvalidPDUException('Invalid UINT32 range, max < ' . $value);
            }

            $value = $filtered;
        }

        if ($required && empty($value)) {
            throw new InvalidPDUException('Required UINT08 value');
        }

        return pack('C', $value ?? 0);
    }

    private function encodeUint16(bool $required, $value): string
    {
        if (null !== $value) {
            $filtered = filter_var($value, FILTER_VALIDATE_INT);
            if (false === $filtered) {
                throw new InvalidPDUException(
                    'Invalid UINT16 value, got ' . (is_object($value) ? get_class($value) : gettype($value))
                );
            }

            if (0 > $filtered) {
                throw new InvalidPDUException('Invalid UINT32 range, min > ' . $value);
            }

            if (0xFFFF < $filtered) {
                throw new InvalidPDUException('Invalid UINT32 range, max < ' . $value);
            }

            $value = $filtered;
        }

        if ($required && empty($value)) {
            throw new InvalidPDUException('Required UINT16 value');
        }

        return pack('n', $value ?? 0);
    }

    private function encodeUint32(bool $required, $value): string
    {
        if (null !== $value) {
            $filtered = filter_var($value, FILTER_VALIDATE_INT);
            if (false === $filtered) {
                throw new InvalidPDUException(
                    'Invalid UINT32 value, got ' . (is_object($value) ? get_class($value) : gettype($value))
                );
            }

            if (0 > $filtered) {
                throw new InvalidPDUException('Invalid UINT32 range, min > ' . $value);
            }

            if (0xFFFFFFFF < $filtered) {
                throw new InvalidPDUException('Invalid UINT32 range, max < ' . $value);
            }

            $value = $filtered;
        }

        if ($required && empty($value)) {
            throw new InvalidPDUException('Required UINT32 value');
        }

        return pack('N', $value ?? 0);
    }

    private function encodeString(bool $required, ?int $min, ?int $max, $value): string
    {
        if (null !== $value) {
            if (!is_string($value)) {
                throw new InvalidPDUException(
                    'Invalid STRING value, got ' . (is_object($value) ? get_class($value) : gettype($value))
                );
            }

            if ((null !== $min && strlen($value) < $min) || (null !== $max && strlen($value) > $max)) {
                throw new InvalidPDUException('Invalid STRING length');
            }

            $value = trim($value);
        }

        if ($required && empty($value)) {
            throw new InvalidPDUException('Required STRING value');
        }

        return $value . "\0";
    }

    private function encodeAddress(bool $required, int $max, $value): string
    {
        if (null !== $value) {
            if (!$value instanceof Address) {
                throw new InvalidPDUException(
                    'Invalid ADDRESS value, got ' . (is_object($value) ? get_class($value) : gettype($value))
                );
            }

            $value = $this->encodeUint08(false, $value->getTON())
                . $this->encodeUint08(false, $value->getNPI())
                . $this->encodeString(false, null, $max, $value->getValue());
        }

        if ($required && empty($value)) {
            throw new InvalidPDUException('Required ADDRESS value');
        }

        return $value;
    }

    private function encodeDateTime(bool $required, $value): string
    {
        if (null !== $value) {
            if (!$value instanceof \DateTimeInterface) {
                throw new InvalidPDUException(
                    'Invalid DATETIME value, got ' . (is_object($value) ? get_class($value) : gettype($value))
                );
            }

            $value = $value->format('ymdHis') . '000+';
        }

        if ($required && empty($value)) {
            throw new InvalidPDUException('Required DATETIME value');
        }

        return $value . "\0";
    }

    private function encodeTLV($value): string
    {
        if (!$value instanceof TLV) {
            throw new InvalidPDUException(
                'Invalid TLV value, got ' . (is_object($value) ? get_class($value) : gettype($value))
            );
        }

        switch ($value->getTag()) {
            case TLV::TAG_DEST_ADDR_SUBUNIT:
            case TLV::TAG_DEST_NETWORK_TYPE:
            case TLV::TAG_DEST_BEARER_TYPE:
            case TLV::TAG_SOURCE_ADDR_SUBUNIT:
            case TLV::TAG_SOURCE_NETWORK_TYPE:
            case TLV::TAG_SOURCE_BEARER_TYPE:
            case TLV::TAG_PAYLOAD_TYPE:
            case TLV::TAG_MS_MSG_WAIT_FACILITIES:
            case TLV::TAG_MS_AVAILABILITY_STATUS:
            case TLV::TAG_MS_VALIDITY:
            case TLV::TAG_PRIVACY_INDICATOR:
            case TLV::TAG_USER_RESPONSE_CODE:
            case TLV::TAG_LANGUAGE_INDICATOR:
            case TLV::TAG_SAR_TOTAL_SEGMENTS:
            case TLV::TAG_SAR_SEGMENT_SEQNUM:
            case TLV::TAG_SC_INTERFACE_VERSION:
            case TLV::TAG_CALLBACK_NUM_PRES_IND:
            case TLV::TAG_NUMBER_OF_MESSAGES:
            case TLV::TAG_DPF_RESULT:
            case TLV::TAG_SET_DPF:
            case TLV::TAG_DELIVERY_FAILURE_REASON:
            case TLV::TAG_MORE_MESSAGES_TO_SEND:
            case TLV::TAG_MESSAGE_STATE:
            case TLV::TAG_USSD_SERVICE_OP:
            case TLV::TAG_DISPLAY_TIME:
            case TLV::TAG_ITS_REPLY_TYPE:
                $buffer = $this->encodeUint08(false, $value->getValue());
                $length = 1;
                break;
            case TLV::TAG_DESTINATION_PORT:
            case TLV::TAG_DEST_TELEMATICS_ID:
            case TLV::TAG_SOURCE_PORT:
            case TLV::TAG_SOURCE_TELEMATICS_ID:
            case TLV::TAG_USER_MESSAGE_REFERENCE:
            case TLV::TAG_SAR_MSG_REF_NUM:
            case TLV::TAG_SMS_SIGNAL:
            case TLV::TAG_ITS_SESSION_INFO:
                $buffer = $this->encodeUint16(false, $value->getValue());
                $length = 2;
                break;
            case TLV::TAG_QOS_TIME_TO_LIVE:
                $buffer = $this->encodeUint32(false, $value->getValue());
                $length = 4;
                break;
            case TLV::TAG_SOURCE_SUBADDRESS:
            case TLV::TAG_DEST_SUBADDRESS:
                $buffer = $this->encodeString(true, 2, 23, $value->getValue());
                $length = strlen($value->getValue());
                break;
            case TLV::TAG_RECEIPTED_MESSAGE_ID:
            case TLV::TAG_CALLBACK_NUM_ATAG:
                $buffer = $this->encodeString(true, null, 65, $value->getValue());
                $length = strlen($value->getValue());
                break;
            case TLV::TAG_ADDITIONAL_STATUS_INFO_TEXT:
                $buffer = $this->encodeString(true, null, 256, $value->getValue());
                $length = strlen($value->getValue());
                break;
            case TLV::TAG_CALLBACK_NUM:
                $buffer = $this->encodeString(true, 4, 19, $value->getValue());
                $length = strlen($value->getValue());
                break;
            case TLV::TAG_NETWORK_ERROR_CODE:
                $buffer = $this->encodeString(true, 3, 3, $value->getValue());
                $length = strlen($value->getValue());
                break;
            case TLV::TAG_MESSAGE_PAYLOAD:
                $buffer = $this->encodeString(true, null, null, $value->getValue());
                $length = strlen($value->getValue());
                break;
            case TLV::TAG_ALERT_ON_MESSAGE_DELIVERY:
            default:
                $buffer = null;
                $length = 0;
        }

        return $this->encodeUint16(true, $value->getTag()) . $this->encodeUint16(true, $length) . $buffer;
    }
}
