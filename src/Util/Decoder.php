<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\DateTime;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\DTO\TLV;
use PE\Component\SMPP\Exception\DecoderException;
use PE\Component\SMPP\Exception\ExceptionInterface;
use PE\Component\SMPP\Exception\UnknownPDUException;

final class Decoder implements DecoderInterface
{
    private ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator = null)
    {
        $this->validator = $validator ?: new Validator();
    }

    public function decode(string $buffer): PDU
    {
        $pos    = 0;
        $id     = $this->decodeUint32($buffer, $pos);
        $status = $this->decodeUint32($buffer, $pos);
        $seqNum = $this->decodeUint32($buffer, $pos);

        try {
            $params = $this->decodeRequiredParams($id, $buffer, $pos);
        } catch (DecoderException $ex) {
            $ex->setCommandID($id);
            throw $ex;
        }

        while (strlen($buffer) > $pos) {//TODO decodeOptionalParams
            $tlv = $this->decodeTLV($buffer, $pos);
            $params[$tlv->getTag()] = $tlv;
        }

        $pdu = new PDU($id, $status, $seqNum, $params ?? []);

        if (PDU::STATUS_NO_ERROR === $status) {
            try {
                //$this->validator->validate($pdu);//TODO move to connection for allow debug body
            } catch (ValidatorException $ex) {
                $ex = new DecoderException($ex->getMessage(), $ex);
                $ex->setCommandID($id);
                throw $ex;
            }
        }

        return $pdu;
    }

    private function decodeRequiredParams(int $id, string $buffer, int &$pos): array
    {
        //TODO check string limits, or remove all here and check in validator
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
                    PDU::KEY_SYSTEM_ID         => $this->decodeString($buffer, $pos, 16),//15 chars + "\0"
                    PDU::KEY_PASSWORD          => $this->decodeString($buffer, $pos, 9),//8 chars + "\0"
                    PDU::KEY_SYSTEM_TYPE       => $this->decodeString($buffer, $pos, 13),//12 chars + "\0"
                    PDU::KEY_INTERFACE_VERSION => $this->decodeUint08($buffer, $pos),
                    PDU::KEY_ADDRESS           => $this->decodeAddress($buffer, $pos, 41),//40 chars + "\0"
                ];
                break;
            case PDU::ID_BIND_RECEIVER_RESP:
            case PDU::ID_BIND_TRANSMITTER_RESP:
            case PDU::ID_BIND_TRANSCEIVER_RESP:
                $params = [PDU::KEY_SYSTEM_ID => $this->decodeString($buffer, $pos, 16)];//15 chars + "\0"
                break;
            case PDU::ID_SUBMIT_SM:
            case PDU::ID_DELIVER_SM:
                $params = [
                    PDU::KEY_SERVICE_TYPE       => $this->decodeString($buffer, $pos, 6),//5 chars + "\0"
                    PDU::KEY_SRC_ADDRESS        => $this->decodeAddress($buffer, $pos, 21),//20 chars + "\0"
                    PDU::KEY_DST_ADDRESS        => $this->decodeAddress($buffer, $pos, 21),//20 chars + "\0"
                    PDU::KEY_ESM_CLASS          => $this->decodeUint08($buffer, $pos),
                    PDU::KEY_PROTOCOL_ID        => $this->decodeUint08($buffer, $pos),
                    PDU::KEY_PRIORITY_FLAG      => $this->decodeUint08($buffer, $pos),
                    PDU::KEY_SCHEDULED_AT       => $this->decodeDateTime($buffer, $pos),
                    PDU::KEY_VALIDITY_PERIOD    => $this->decodeDateTime($buffer, $pos),
                    PDU::KEY_REG_DELIVERY       => $this->decodeUint08($buffer, $pos),
                    PDU::KEY_REPLACE_IF_PRESENT => $this->decodeUint08($buffer, $pos),
                    PDU::KEY_DATA_CODING        => $this->decodeUint08($buffer, $pos),
                    PDU::KEY_SM_DEFAULT_MSG_ID  => $this->decodeUint08($buffer, $pos),
                    PDU::KEY_SM_LENGTH          => $this->decodeUint08($buffer, $pos),
                    PDU::KEY_SHORT_MESSAGE      => $this->decodeString($buffer, $pos, 254),//254 chars without "\0"
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
                $params = [PDU::KEY_MESSAGE_ID => $this->decodeString($buffer, $pos, 65)];//64 chars + "\0"
                break;
            case PDU::ID_DATA_SM:
                $params = [
                    PDU::KEY_SERVICE_TYPE => $this->decodeString($buffer, $pos, 6),//5 chars + "\0"
                    PDU::KEY_SRC_ADDRESS  => $this->decodeAddress($buffer, $pos, 21),//20 chars + "\0"
                    PDU::KEY_DST_ADDRESS  => $this->decodeAddress($buffer, $pos, 21),//20 chars + "\0"
                    PDU::KEY_ESM_CLASS    => $this->decodeUint08($buffer, $pos),
                    PDU::KEY_REG_DELIVERY => $this->decodeUint08($buffer, $pos),
                    PDU::KEY_DATA_CODING  => $this->decodeUint08($buffer, $pos),
                ];
                break;
            case PDU::ID_QUERY_SM:
                $params = [
                    PDU::KEY_MESSAGE_ID  => $this->decodeString($buffer, $pos, 65),//64 chars + "\0"
                    PDU::KEY_SRC_ADDRESS => $this->decodeAddress($buffer, $pos, 21),//20 chars + "\0"
                ];
                break;
            case PDU::ID_QUERY_SM_RESP:
                $params = [
                    PDU::KEY_MESSAGE_ID    => $this->decodeString($buffer, $pos, 65),//64 chars + "\0"
                    PDU::KEY_FINAL_DATE    => $this->decodeDateTime($buffer, $pos),
                    PDU::KEY_MESSAGE_STATE => $this->decodeUint08($buffer, $pos),
                    PDU::KEY_ERROR_CODE    => $this->decodeUint08($buffer, $pos),
                ];
                break;
            case PDU::ID_CANCEL_SM:
                $params = [
                    PDU::KEY_MESSAGE_ID  => $this->decodeString($buffer, $pos, 16),//15 chars + "\0"
                    PDU::KEY_SRC_ADDRESS => $this->decodeAddress($buffer, $pos, 21),//20 chars + "\0"
                    PDU::KEY_DST_ADDRESS => $this->decodeAddress($buffer, $pos, 21),//20 chars + "\0"
                ];
                break;
            case PDU::ID_REPLACE_SM:
                $params = [
                    PDU::KEY_MESSAGE_ID        => $this->decodeString($buffer, $pos, 65),//64 chars + "\0"
                    PDU::KEY_SRC_ADDRESS       => $this->decodeAddress($buffer, $pos, 21),//20 chars + "\0"
                    PDU::KEY_SCHEDULED_AT      => $this->decodeDateTime($buffer, $pos),
                    PDU::KEY_VALIDITY_PERIOD   => $this->decodeDateTime($buffer, $pos),
                    PDU::KEY_REG_DELIVERY      => $this->decodeUint08($buffer, $pos),
                    PDU::KEY_SM_DEFAULT_MSG_ID => $this->decodeUint08($buffer, $pos),
                    PDU::KEY_SM_LENGTH         => $this->decodeUint08($buffer, $pos),
                    PDU::KEY_SHORT_MESSAGE     => $this->decodeString($buffer, $pos, 254),//254 chars without "\0"
                ];
                break;
            case PDU::ID_ALERT_NOTIFICATION:
                $params = [
                    PDU::KEY_SRC_ADDRESS  => $this->decodeAddress($buffer, $pos, 65),//64 chars + "\0"
                    PDU::KEY_ESME_ADDRESS => $this->decodeAddress($buffer, $pos, 65),//64 chars + "\0"
                ];
                break;
            default:
                throw new UnknownPDUException(sprintf('Unknown pdu id: 0x%08X', $id));
        }

        return $params ?? [];
    }

    private function decodeUint08(string $buffer, int &$pos): int
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
            throw new DecoderException($error);
        }

        $pos += 1;
        return $value[1];
    }

    private function decodeUint16(string $buffer, int &$pos): int
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
            throw new DecoderException($error);
        }

        $pos += 2;
        return $value[1];
    }

    private function decodeUint32(string $buffer, int &$pos): int
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
            throw new DecoderException($error);
        }

        $pos += 4;
        return $value[1];
    }

    private function decodeString(string $buffer, int &$pos, ?int $limit): string
    {
        $value = '';
        while (strlen($buffer) > $pos) {
            $value .= $buffer[$pos++];
            if ("\0" === $buffer[$pos - 1]) {
                break;
            }
            if ($limit > 0 && strlen($value) === $limit) {
                break;
            }
        }

        return rtrim($value, "\0");
    }

    private function decodeStringOld(string $buffer, int &$pos, bool $required, ?int $min, ?int $max): ?string
    {
        $error = sprintf('Required STRING value at position %d in "%s"', $pos, $this->toPrintable($buffer));
        $value = '';

        while (strlen($buffer) > $pos && $buffer[$pos] !== "\0" && strlen($value) < $max) {
            $value .= $buffer[$pos++];
        }
        $pos++;//<-- skip null char

        if (null !== $min && strlen($value) < $min) {
            throw new DecoderException(str_replace('Required STRING value', 'Invalid STRING length', $error));
        }

        if ($required && '' === $value) {
            throw new DecoderException($error);
        }

        return $value ?: null;
    }

    private function decodeAddress(string $buffer, int &$pos, ?int $limit): ?Address
    {
        $ton   = $this->decodeUint08($buffer, $pos);
        $npi   = $this->decodeUint08($buffer, $pos);
        $value = $this->decodeString($buffer, $pos, $limit);

        return !empty($value) ? new Address($ton, $npi, $value) : null;
    }

    private function decodeDateTime(string $buffer, int &$pos): ?\DateTimeInterface
    {
        $value = $this->decodeString($buffer, $pos, 17);//16 chars + "\0"

        if (!empty($value)) {
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
                throw new DecoderException('Invalid DATETIME param');
            }

            $value = $datetime;
        }

        return !empty($value) ? $value : null;
    }

    private function decodeTLV(string $buffer, int &$pos): TLV
    {
        //dump(substr($buffer, $pos));
        //TODO here all string values may be not null terminated, so use limit
        if ((strlen($buffer) - $pos) < 4) {
            throw new DecoderException('Malformed TLV header');
        }

        $tag    = $this->decodeUint16($buffer, $pos);
        $length = $this->decodeUint16($buffer, $pos);

        if ((strlen($buffer) - $pos) < $length) {
            throw new DecoderException('Malformed TLV value');
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
                $value = $this->decodeUint08($buffer, $pos);
                break;
            case TLV::TAG_DESTINATION_PORT:
            case TLV::TAG_DEST_TELEMATICS_ID:
            case TLV::TAG_SOURCE_PORT:
            case TLV::TAG_SOURCE_TELEMATICS_ID:
            case TLV::TAG_USER_MESSAGE_REFERENCE:
            case TLV::TAG_SAR_MSG_REF_NUM:
            case TLV::TAG_SMS_SIGNAL:
            case TLV::TAG_ITS_SESSION_INFO:
                $value = $this->decodeUint16($buffer, $pos);
                break;
            case TLV::TAG_QOS_TIME_TO_LIVE:
                $value = $this->decodeUint32($buffer, $pos);
                break;
            case TLV::TAG_SOURCE_SUBADDRESS:
            case TLV::TAG_DEST_SUBADDRESS:
                $value = $this->decodeString($buffer, $pos, 23);//may be not null terminated
                break;
            case TLV::TAG_RECEIPTED_MESSAGE_ID:
            case TLV::TAG_CALLBACK_NUM_ATAG:
                $value = $this->decodeString($buffer, $pos, 65);//null terminated
                break;
            case TLV::TAG_ADDITIONAL_STATUS_INFO_TEXT:
                $value = $this->decodeString($buffer, $pos, 256);//null terminated
                break;
            case TLV::TAG_CALLBACK_NUM:
                $value = $this->decodeString($buffer, $pos, 19);//may be not null terminated
                break;
            case TLV::TAG_NETWORK_ERROR_CODE:
                $value = $this->decodeString($buffer, $pos, 3);//may be not null terminated
                break;
            case TLV::TAG_MESSAGE_PAYLOAD:
                $value = $this->decodeString($buffer, $pos, 65535);//may be not null terminated
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
