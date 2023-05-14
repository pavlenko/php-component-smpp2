<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\DateTime;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\DTO\TLV;
use PE\Component\SMPP\Exception\InvalidPDUException;
use PE\Component\SMPP\Exception\MalformedPDUException;
use PE\Component\SMPP\Exception\UnknownPDUException;

final class Decoder
{
    public function decode(string $buffer): PDU
    {
        $pos    = 0;
        $id     = (int) $this->decodeUint32($buffer, $pos, false);
        $status = (int) $this->decodeUint32($buffer, $pos, false);
        $seqNum = (int) $this->decodeUint32($buffer, $pos, false);

        switch ($id) {
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
                $params = [
                    PDU::KEY_SYSTEM_ID         => $this->decodeString($buffer, $pos, true, null, 16),
                    PDU::KEY_PASSWORD          => $this->decodeString($buffer, $pos, false, null, 9),
                    PDU::KEY_SYSTEM_TYPE       => $this->decodeString($buffer, $pos, false, null, 13),
                    PDU::KEY_INTERFACE_VERSION => $this->decodeUint08($buffer, $pos, true),
                    PDU::KEY_ADDRESS           => $this->decodeAddress($buffer, $pos, false, 41),
                ];
                break;
            case PDU::ID_BIND_RECEIVER_RESP:
            case PDU::ID_BIND_TRANSMITTER_RESP:
            case PDU::ID_BIND_TRANSCEIVER_RESP:
                $params = [PDU::KEY_SYSTEM_ID => $this->decodeString($buffer, $pos, true, null, 16)];
                break;
            case PDU::ID_SUBMIT_SM:
            case PDU::ID_DELIVER_SM:
                $params = [
                    PDU::KEY_SERVICE_TYPE       => $this->decodeString($buffer, $pos, false, null, 6),
                    PDU::KEY_SRC_ADDRESS        => $this->decodeAddress($buffer, $pos, false, 21),
                    PDU::KEY_DST_ADDRESS        => $this->decodeAddress($buffer, $pos, true, 21),
                    PDU::KEY_ESM_CLASS          => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_PROTOCOL_ID        => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_PRIORITY_FLAG      => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SCHEDULED_AT       => $this->decodeDateTime($buffer, $pos, false),
                    PDU::KEY_VALIDITY_PERIOD    => $this->decodeDateTime($buffer, $pos, false),
                    PDU::KEY_REG_DELIVERY       => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_REPLACE_IF_PRESENT => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_DATA_CODING        => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SM_DEFAULT_MSG_ID  => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SM_LENGTH          => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SHORT_MESSAGE      => $this->decodeString($buffer, $pos, true, null, 254),
                ];
                if (PDU::ID_DELIVER_SM === $id) {
                    unset(
                        $params[PDU::KEY_SCHEDULED_AT],
                        $params[PDU::KEY_VALIDITY_PERIOD],
                        $params[PDU::KEY_REPLACE_IF_PRESENT],
                        $params[PDU::KEY_SM_DEFAULT_MSG_ID],
                    );
                }
                break;
            case PDU::ID_SUBMIT_SM_RESP:
            case PDU::ID_DELIVER_SM_RESP:
            case PDU::ID_DATA_SM_RESP:
                $params = [PDU::KEY_MESSAGE_ID => $this->decodeString($buffer, $pos, true, null, 65)];
                break;
            case PDU::ID_DATA_SM:
                $params = [
                    PDU::KEY_SERVICE_TYPE => $this->decodeString($buffer, $pos, false, null, 6),
                    PDU::KEY_SRC_ADDRESS  => $this->decodeAddress($buffer, $pos, false, 21),
                    PDU::KEY_DST_ADDRESS  => $this->decodeAddress($buffer, $pos, true, 21),
                    PDU::KEY_ESM_CLASS    => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_REG_DELIVERY => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_DATA_CODING  => (int) $this->decodeUint08($buffer, $pos, false),
                ];
                break;
            case PDU::ID_QUERY_SM:
                $params = [
                    PDU::KEY_MESSAGE_ID  => $this->decodeString($buffer, $pos, true, null, 65),
                    PDU::KEY_SRC_ADDRESS => $this->decodeAddress($buffer, $pos, false, 21),
                ];
                break;
            case PDU::ID_QUERY_SM_RESP:
                $params = [
                    PDU::KEY_MESSAGE_ID    => $this->decodeString($buffer, $pos, true, null, 65),
                    PDU::KEY_FINAL_DATE    => $this->decodeDateTime($buffer, $pos, false),
                    PDU::KEY_MESSAGE_STATE => $this->decodeUint08($buffer, $pos, true),
                    PDU::KEY_ERROR_CODE    => $this->decodeUint08($buffer, $pos, false),
                ];
                break;
            case PDU::ID_CANCEL_SM:
                $params = [
                    PDU::KEY_MESSAGE_ID  => $this->decodeString($buffer, $pos, true, null, 65),
                    PDU::KEY_SRC_ADDRESS => $this->decodeAddress($buffer, $pos, true, 21),
                    PDU::KEY_DST_ADDRESS => $this->decodeAddress($buffer, $pos, false, 21),
                ];
                break;
            case PDU::ID_REPLACE_SM:
                $params = [
                    PDU::KEY_MESSAGE_ID        => $this->decodeString($buffer, $pos, true, null, 65),
                    PDU::KEY_SRC_ADDRESS       => $this->decodeAddress($buffer, $pos, true, 21),
                    PDU::KEY_SCHEDULED_AT      => $this->decodeDateTime($buffer, $pos, false),
                    PDU::KEY_VALIDITY_PERIOD   => $this->decodeDateTime($buffer, $pos, false),
                    PDU::KEY_REG_DELIVERY      => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SM_DEFAULT_MSG_ID => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SM_LENGTH         => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SHORT_MESSAGE     => $this->decodeString($buffer, $pos, true, null, 254),
                ];
                break;
            case PDU::ID_ALERT_NOTIFICATION:
                $params = [
                    PDU::KEY_SRC_ADDRESS  => $this->decodeAddress($buffer, $pos, true, 65),
                    PDU::KEY_ESME_ADDRESS => $this->decodeAddress($buffer, $pos, true, 65),
                ];
                break;
            default:
                throw new UnknownPDUException(sprintf('Unknown pdu id: 0x%08X', $id));
        }

        while (strlen($buffer) > $pos) {
            $tlv = $this->decodeTLV($buffer, $pos);
            $params[$tlv->getTag()] = $tlv->getValue();
        }

        return new PDU($id, $status, $seqNum, $params ?? []);
    }

    private function decodeUint08(string $buffer, int &$pos, bool $required): ?int
    {
        $error = null;
        set_error_handler(function ($_, $message) use (&$error) {
            $error = $message;
            if (false !== ($pos = strpos($error, '): '))) {
                $error = substr($error, $pos + 3);
            }
        });
        $value = unpack('C', $buffer, $pos);
        restore_error_handler();

        if (false === $value) {
            throw new MalformedPDUException($error);
        }

        if ($required && 0 === $value[1]) {
            $error = sprintf('Required UINT08 value at position %d in "%s"', $pos, $this->toPrintable($buffer));
            throw new InvalidPDUException($error);
        }

        $pos += 1;
        return $value[1];
    }

    private function decodeUint16(string $buffer, int &$pos, bool $required): ?int
    {
        $error = null;
        set_error_handler(function ($_, $message) use (&$error) {
            $error = $message;
            if (false !== ($pos = strpos($error, '): '))) {
                $error = substr($error, $pos + 3);
            }
        });
        $value = unpack('n', $buffer, $pos);
        restore_error_handler();

        if (false === $value) {
            throw new MalformedPDUException($error);
        }

        if ($required && 0 === $value[1]) {
            $error = sprintf('Required UINT16 value at position %d in "%s"', $pos, $this->toPrintable($buffer));
            throw new InvalidPDUException($error);
        }

        $pos += 2;
        return $value[1];
    }

    private function decodeUint32(string $buffer, int &$pos, bool $required): ?int
    {
        $error = null;
        set_error_handler(function ($_, $message) use (&$error) {
            $error = $message;
            if (false !== ($pos = strpos($error, '): '))) {
                $error = substr($error, $pos + 3);
            }
        });
        $value = unpack('N', $buffer, $pos);
        restore_error_handler();

        if (false === $value) {
            throw new MalformedPDUException($error);
        }

        if ($required && 0 === $value[1]) {
            dump($buffer);
            $error = sprintf('Required UINT32 value at position %d in "%s"', $pos, $this->toPrintable($buffer));
            throw new InvalidPDUException($error);
        }

        $pos += 4;
        return $value[1];
    }

    private function decodeString(string $buffer, int &$pos, bool $required, ?int $min, ?int $max): ?string
    {
        $error = sprintf('Required STRING value at position %d in "%s"', $pos, $this->toPrintable($buffer));
        $value = '';

        while (strlen($buffer) > $pos && $buffer[$pos] !== "\0" && strlen($value) < $max) {
            $value .= $buffer[$pos++];
        }
        $pos++;//<-- skip null char

        if (null !== $min && strlen($value) < $min) {
            throw new MalformedPDUException(str_replace('Required STRING value', 'Invalid STRING length', $error));
        }

        if ($required && '' === $value) {
            throw new InvalidPDUException($error);
        }

        return $value ?: null;
    }

    private function decodeAddress(string $buffer, int &$pos, bool $required, int $max): ?Address
    {
        $error = sprintf('Required ADDRESS value at position %d in "%s"', $pos, $this->toPrintable($buffer));
        $ton   = $this->decodeUint08($buffer, $pos, false);
        $npi   = $this->decodeUint08($buffer, $pos, false);
        $value = $this->decodeString($buffer, $pos, false, null, $max);

        if ($required && null === $value) {
            throw new InvalidPDUException($error);
        }

        return null !== $value ? new Address((int) $ton, (int) $npi, $value) : null;
    }

    private function decodeDateTime(string $buffer, int &$pos, bool $required): ?\DateTimeInterface
    {
        $error = sprintf('Malformed DATETIME _PARAM_ at position %d in "%s"', $pos, $this->toPrintable($buffer));
        $value = $this->decodeString($buffer, $pos, false, null, 16);//16 = 17 chars includes "\0" which is trimmed

        if (null !== $value) {
            if (strlen($value) !== 16) {
                throw new MalformedPDUException(str_replace('_PARAM_', 'invalid length'.strlen($value), $error));
            }

            $datetime = substr($value, 0, 13) . '00';
            $offset   = substr($value, 13, 2) * 900;
            $relative = substr($value, 15, 1);
            if ($relative === '-') {
                $offset *= -1;
            }

            $datetime = DateTime::createFromFormat(
                'ymdHisv',
                $datetime,
                new \DateTimeZone(($offset < 0 ? '-' : '+') . gmdate('Hi', abs($offset)))
            );

            if (false === $datetime) {
                throw new MalformedPDUException(str_replace('_PARAM_', 'invalid format', $error));
            }

            $value = $datetime;
        }

        if ($required && null === $value) {
            $error = sprintf('Required DATETIME value at position %d in "%s"', $pos, $this->toPrintable($buffer));
            throw new InvalidPDUException($error);
        }

        return $value;
    }

    private function decodeTLV(string $buffer, int &$pos): TLV
    {
        if ((strlen($buffer) - $pos) < 4) {
            throw new MalformedPDUException('Malformed TLV header');
        }

        $tag    = $this->decodeUint16($buffer, $pos, true);
        $length = $this->decodeUint16($buffer, $pos, true);

        if ((strlen($buffer) - $pos) < $length) {
            throw new MalformedPDUException('Malformed TLV value');
        }

        switch ($tag) {
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
                $value = (int) $this->decodeUint08($buffer, $pos, false);
                break;
            case TLV::TAG_DESTINATION_PORT:
            case TLV::TAG_DEST_TELEMATICS_ID:
            case TLV::TAG_SOURCE_PORT:
            case TLV::TAG_SOURCE_TELEMATICS_ID:
            case TLV::TAG_USER_MESSAGE_REFERENCE:
            case TLV::TAG_SAR_MSG_REF_NUM:
            case TLV::TAG_SMS_SIGNAL:
            case TLV::TAG_ITS_SESSION_INFO:
                $value = (int) $this->decodeUint16($buffer, $pos, false);
                break;
            case TLV::TAG_QOS_TIME_TO_LIVE:
                $value = (int) $this->decodeUint32($buffer, $pos, false);
                break;
            case TLV::TAG_SOURCE_SUBADDRESS:
            case TLV::TAG_DEST_SUBADDRESS:
                $value = $this->decodeString($buffer, $pos, true, 2, 23);
                break;
            case TLV::TAG_RECEIPTED_MESSAGE_ID:
            case TLV::TAG_CALLBACK_NUM_ATAG:
                $value = $this->decodeString($buffer, $pos, true, null, 65);
                break;
            case TLV::TAG_ADDITIONAL_STATUS_INFO_TEXT:
                $value = $this->decodeString($buffer, $pos, true, null, 256);
                break;
            case TLV::TAG_CALLBACK_NUM:
                $value = $this->decodeString($buffer, $pos, true, 4, 19);
                break;
            case TLV::TAG_NETWORK_ERROR_CODE:
                $value = $this->decodeString($buffer, $pos, true, 3, 3);
                break;
            case TLV::TAG_MESSAGE_PAYLOAD:
                $value = $this->decodeString($buffer, $pos, true, null, null);
                break;
            case TLV::TAG_ALERT_ON_MESSAGE_DELIVERY:
            default:
                $value = null;
        }

        $pos += $length;
        return new TLV($tag, $value);
    }

    private function toPrintable(string $value): string
    {
        return preg_replace_callback('/[\x00-\x1F\x7F]+/', function ($c) {
            $map = [
                "\t" => '\t',
                "\n" => '\n',
                "\v" => '\v',
                "\f" => '\f',
                "\r" => '\r',
            ];

            $c = $c[$i = 0];
            $s = '';
            do {
                $s .= $map[$c[$i]] ?? sprintf('\x%02X', \ord($c[$i]));
            } while (isset($c[++$i]));

            return $s;
        }, $value);
    }
}
