<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\PDU;
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
                $this->encodeString($body, true, null, 16, $pdu->get(PDU::KEY_SYSTEM_ID));
                $this->encodeString($body, false, null, 9, $pdu->get(PDU::KEY_PASSWORD));
                $this->encodeString($body, false, null, 13, $pdu->get(PDU::KEY_SYSTEM_TYPE));
                $this->encodeUint08($body, true, $pdu->get(PDU::KEY_INTERFACE_VERSION));
                $this->encodeAddress($body, false, 41, $pdu->get(PDU::KEY_ADDRESS));
                break;
            case PDU::ID_BIND_RECEIVER_RESP:
            case PDU::ID_BIND_TRANSMITTER_RESP:
            case PDU::ID_BIND_TRANSCEIVER_RESP:
                $params = [PDU::KEY_SYSTEM_ID => $this->decodeString($buffer, $pos, true, 16)];
                break;
            case PDU::ID_SUBMIT_SM:
            case PDU::ID_DELIVER_SM:
                $params = [
                    PDU::KEY_SERVICE_TYPE           => $this->decodeString($buffer, $pos, false, 6),
                    PDU::KEY_SRC_ADDRESS            => $this->decodeAddress($buffer, $pos, false, 21),
                    PDU::KEY_DST_ADDRESS            => $this->decodeAddress($buffer, $pos, true, 21),
                    PDU::KEY_ESM_CLASS              => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_PROTOCOL_ID            => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_PRIORITY_FLAG          => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SCHEDULE_DELIVERY_TIME => $this->decodeDateTime($buffer, $pos, false),
                    PDU::KEY_VALIDITY_PERIOD        => $this->decodeDateTime($buffer, $pos, false),
                    PDU::KEY_REG_DELIVERY           => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_REPLACE_IF_PRESENT     => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_DATA_CODING            => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SM_DEFAULT_MSG_ID      => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SM_LENGTH              => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SHORT_MESSAGE          => $this->decodeString($buffer, $pos, true, 254),
                ];
                if (PDU::ID_DELIVER_SM === $id) {
                    unset(
                        $params[PDU::KEY_SCHEDULE_DELIVERY_TIME],
                        $params[PDU::KEY_VALIDITY_PERIOD],
                        $params[PDU::KEY_REPLACE_IF_PRESENT],
                        $params[PDU::KEY_SM_DEFAULT_MSG_ID],
                    );
                }
                break;
            case PDU::ID_SUBMIT_SM_RESP:
            case PDU::ID_DELIVER_SM_RESP:
            case PDU::ID_DATA_SM_RESP:
                $params = [PDU::KEY_MESSAGE_ID => $this->decodeString($buffer, $pos, true, 65)];
                break;
            case PDU::ID_DATA_SM:
                $params = [
                    PDU::KEY_SERVICE_TYPE => $this->decodeString($buffer, $pos, false, 6),
                    PDU::KEY_SRC_ADDRESS  => $this->decodeAddress($buffer, $pos, false, 21),
                    PDU::KEY_DST_ADDRESS  => $this->decodeAddress($buffer, $pos, true, 21),
                    PDU::KEY_ESM_CLASS    => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_REG_DELIVERY => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_DATA_CODING  => (int) $this->decodeUint08($buffer, $pos, false),
                ];
                break;
            case PDU::ID_QUERY_SM:
                $params = [
                    PDU::KEY_MESSAGE_ID  => $this->decodeString($buffer, $pos, true, 65),
                    PDU::KEY_SRC_ADDRESS => $this->decodeAddress($buffer, $pos, false, 21),
                ];
                break;
            case PDU::ID_QUERY_SM_RESP:
                $params = [
                    PDU::KEY_MESSAGE_ID    => $this->decodeString($buffer, $pos, true, 65),
                    PDU::KEY_FINAL_DATE    => $this->decodeDateTime($buffer, $pos, false),
                    PDU::KEY_MESSAGE_STATE => $this->decodeUint08($buffer, $pos, true),
                    PDU::KEY_ERROR_CODE    => $this->decodeUint08($buffer, $pos, false),
                ];
                break;
            case PDU::ID_CANCEL_SM:
                $params = [
                    PDU::KEY_MESSAGE_ID  => $this->decodeString($buffer, $pos, true, 65),
                    PDU::KEY_SRC_ADDRESS => $this->decodeAddress($buffer, $pos, true, 21),
                    PDU::KEY_DST_ADDRESS => $this->decodeAddress($buffer, $pos, false, 21),
                ];
                break;
            case PDU::ID_REPLACE_SM:
                $params = [
                    PDU::KEY_MESSAGE_ID             => $this->decodeString($buffer, $pos, true, 65),
                    PDU::KEY_SRC_ADDRESS            => $this->decodeAddress($buffer, $pos, true, 21),
                    PDU::KEY_SCHEDULE_DELIVERY_TIME => $this->decodeDateTime($buffer, $pos, false),
                    PDU::KEY_VALIDITY_PERIOD        => $this->decodeDateTime($buffer, $pos, false),
                    PDU::KEY_REG_DELIVERY           => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SM_DEFAULT_MSG_ID      => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SM_LENGTH              => (int) $this->decodeUint08($buffer, $pos, false),
                    PDU::KEY_SHORT_MESSAGE          => $this->decodeString($buffer, $pos, true, 254),
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

        $head->writeInt32(strlen($body) + 16);
        $head->writeInt32($pdu->getID());
        $head->writeInt32($pdu->getStatus());
        $head->writeInt32($pdu->getSeqNum());

        return $buffer;
    }

    public function encodeUint08(string &$buffer, bool $required, $value): void
    {}

    public function encodeUint16(string &$buffer, bool $required, $value): void
    {}

    public function encodeUint32(string &$buffer, bool $required, $value): void
    {}

    public function encodeString(string &$buffer, bool $required, int $min = null, int $max = null, $value = null): void
    {}

    public function encodeAddress(string &$buffer, bool $required, $value, int $max = null): void
    {}

    public function encodeDateTime(string &$buffer, bool $required, $value): void
    {}
}
