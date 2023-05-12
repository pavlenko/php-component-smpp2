<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\DTO\TLV;

final class Serializer implements SerializerInterface
{
    public function decode(string $pdu): PDU
    {
        $buffer = new Buffer($pdu);
        $id     = $buffer->shiftInt32();
        $status = $buffer->shiftInt32();
        $seqNum = $buffer->shiftInt32();

        switch ($id) {
            case PDU::ID_GENERIC_NACK:
            case PDU::ID_QUERY_SM:
            case PDU::ID_QUERY_SM_RESP:
            case PDU::ID_UNBIND:
            case PDU::ID_UNBIND_RESP:
            case PDU::ID_ENQUIRE_LINK:
            case PDU::ID_ENQUIRE_LINK_RESP:
            case PDU::ID_CANCEL_SM_RESP:
            case PDU::ID_REPLACE_SM_RESP:
                /* DO NOTHING JUST KNOWN ID */
                break;
            case PDU::ID_BIND_RECEIVER:
            case PDU::ID_BIND_TRANSMITTER:
            case PDU::ID_BIND_TRANSCEIVER:
                $params = [
                    PDU::KEY_SYSTEM_ID         => $buffer->shiftString(16),
                    PDU::KEY_PASSWORD          => $buffer->shiftString(9),
                    PDU::KEY_SYSTEM_TYPE       => $buffer->shiftString(13),
                    PDU::KEY_INTERFACE_VERSION => $buffer->shiftInt8(),
                    PDU::KEY_ADDRESS           => $buffer->shiftAddress(41),
                ];
                break;
            case PDU::ID_BIND_RECEIVER_RESP:
            case PDU::ID_BIND_TRANSMITTER_RESP:
            case PDU::ID_BIND_TRANSCEIVER_RESP:
                $params = [PDU::KEY_SYSTEM_ID => $buffer->shiftString(16)];
                break;
            case PDU::ID_CANCEL_SM:
                $params = [
                    PDU::KEY_SERVICE_TYPE => $buffer->shiftString(6),
                    PDU::KEY_MESSAGE_ID   => $buffer->shiftString(16),
                    PDU::KEY_SRC_ADDRESS  => $buffer->shiftAddress(21),
                    PDU::KEY_DST_ADDRESS  => $buffer->shiftAddress(21),
                ];
                break;
            case PDU::ID_OUT_BIND:
                $params = [
                    PDU::KEY_SYSTEM_ID => $buffer->shiftString(16),
                    PDU::KEY_PASSWORD  => $buffer->shiftString(9),
                ];
                break;
            case PDU::ID_ALERT_NOTIFICATION:
                $params = [
                    PDU::KEY_SRC_ADDRESS  => $buffer->shiftAddress(65),
                    PDU::KEY_ESME_ADDRESS => $buffer->shiftAddress(65),
                ];
                break;
            case PDU::ID_DELIVER_SM:
            case PDU::ID_SUBMIT_SM:
//                $params = [
//                    PDU::KEY_SERVICE_TYPE           => $buffer->shiftString(6),
//                    PDU::KEY_SRC_ADDRESS            => $buffer->shiftAddress(21),
//                    PDU::KEY_DST_ADDRESS            => $buffer->shiftAddress(21),
//                    PDU::KEY_ESM_CLASS              => $buffer->shiftInt8(),
//                    PDU::KEY_PROTOCOL_ID            => $buffer->shiftInt8(),
//                    PDU::KEY_PRIORITY_FLAG          => $buffer->shiftInt8(),
//                    PDU::KEY_SCHEDULE_DELIVERY_TIME => $buffer->shiftDateTime(),//->NULL ID_DELIVER_SM
//                    PDU::KEY_VALIDITY_PERIOD        => $buffer->shiftDateTime(),//->NULL ID_DELIVER_SM
//                    PDU::KEY_REG_DELIVERY           => $buffer->shiftInt8(),
//                    PDU::KEY_REPLACE_IF_PRESENT     => $buffer->shiftInt8(),//->NULL ID_DELIVER_SM
//                    PDU::KEY_DATA_CODING            => $buffer->shiftInt8(),
//                    PDU::KEY_SM_DEFAULT_MSG_ID      => $buffer->shiftInt8(),//->NULL ID_DELIVER_SM
//                    PDU::KEY_SM_LENGTH              => $buffer->shiftInt8(),
//                    PDU::KEY_SHORT_MESSAGE          => $buffer->shiftString(254),
//                ];
                $params = (new Decoder())->decode($pdu)->getParams();
                break;
            case PDU::ID_DELIVER_SM_RESP:
            case PDU::ID_SUBMIT_SM_RESP:
            case PDU::ID_DATA_SM_RESP:
                $params = [PDU::KEY_MESSAGE_ID => $buffer->shiftString(65)];
                break;
            case PDU::ID_REPLACE_SM:
                $params = [
                    PDU::KEY_MESSAGE_ID             => $buffer->shiftString(65),
                    PDU::KEY_SRC_ADDRESS            => $buffer->shiftAddress(21),
                    PDU::KEY_SCHEDULE_DELIVERY_TIME => $buffer->shiftDateTime(),
                    PDU::KEY_VALIDITY_PERIOD        => $buffer->shiftDateTime(),
                    PDU::KEY_REG_DELIVERY           => $buffer->shiftInt8(),
                    PDU::KEY_SM_DEFAULT_MSG_ID      => $buffer->shiftInt8(),
                    PDU::KEY_SM_LENGTH              => $buffer->shiftInt8(),
                    PDU::KEY_SHORT_MESSAGE          => $buffer->shiftString(254),
                ];
                break;
            case PDU::ID_DATA_SM:
                $params = [
                    PDU::KEY_SERVICE_TYPE => $buffer->shiftString(6),
                    PDU::KEY_SRC_ADDRESS  => $buffer->shiftAddress(21),
                    PDU::KEY_DST_ADDRESS  => $buffer->shiftAddress(21),
                    PDU::KEY_ESM_CLASS    => $buffer->shiftInt8(),
                    PDU::KEY_REG_DELIVERY => $buffer->shiftInt8(),
                    PDU::KEY_DATA_CODING  => $buffer->shiftInt8(),
                ];
                break;
            default:
                throw new \UnexpectedValueException(sprintf('Unexpected PDU id: 0x%08X', $id));
        }

        while (!$buffer->isEOF()) {
            try {
                $tlv = $buffer->shiftTLV();
                $params[$tlv->getTag()] = $tlv->getValue();
            } catch (\Throwable $e) {
                break;
            }
        }

        return new PDU($id, $status, $seqNum, $params ?? []);
    }

    public function encode(PDU $pdu): string
    {
        $head = new Buffer();
        $body = new Buffer();

        switch ($pdu->getID()) {
            case PDU::ID_GENERIC_NACK:
            case PDU::ID_QUERY_SM:
            case PDU::ID_QUERY_SM_RESP:
            case PDU::ID_UNBIND:
            case PDU::ID_UNBIND_RESP:
            case PDU::ID_ENQUIRE_LINK:
            case PDU::ID_ENQUIRE_LINK_RESP:
            case PDU::ID_CANCEL_SM_RESP:
            case PDU::ID_REPLACE_SM_RESP:
                /* DO NOTHING JUST KNOWN ID */
                break;
            case PDU::ID_BIND_RECEIVER:
            case PDU::ID_BIND_TRANSMITTER:
            case PDU::ID_BIND_TRANSCEIVER:
                $body->writeString($pdu->get(PDU::KEY_SYSTEM_ID));
                $body->writeString($pdu->get(PDU::KEY_PASSWORD));
                $body->writeString($pdu->get(PDU::KEY_SYSTEM_TYPE));
                $body->writeInt8($pdu->get(PDU::KEY_INTERFACE_VERSION));
                $body->writeAddress($pdu->get(PDU::KEY_ADDRESS));
                break;
            case PDU::ID_BIND_RECEIVER_RESP:
            case PDU::ID_BIND_TRANSMITTER_RESP:
            case PDU::ID_BIND_TRANSCEIVER_RESP:
                $body->writeString($pdu->get(PDU::KEY_SYSTEM_ID));
                break;
            case PDU::ID_CANCEL_SM:
                $body->writeString($pdu->get(PDU::KEY_SERVICE_TYPE));
                $body->writeString($pdu->get(PDU::KEY_MESSAGE_ID));
                $body->writeAddress($pdu->get(PDU::KEY_SRC_ADDRESS));
                $body->writeAddress($pdu->get(PDU::KEY_DST_ADDRESS));
                break;
            case PDU::ID_OUT_BIND:
                $body->writeString($pdu->get(PDU::KEY_SYSTEM_ID));
                $body->writeString($pdu->get(PDU::KEY_PASSWORD));
                break;
            case PDU::ID_ALERT_NOTIFICATION:
                $body->writeAddress($pdu->get(PDU::KEY_SRC_ADDRESS));
                $body->writeAddress($pdu->get(PDU::KEY_ESME_ADDRESS));
                break;
            case PDU::ID_DELIVER_SM:
                $body->writeString($pdu->get(PDU::KEY_SERVICE_TYPE));
                $body->writeAddress($pdu->get(PDU::KEY_SRC_ADDRESS));
                $body->writeAddress($pdu->get(PDU::KEY_DST_ADDRESS));
                $body->writeInt8($pdu->get(PDU::KEY_ESM_CLASS));
                $body->writeInt8($pdu->get(PDU::KEY_PROTOCOL_ID));
                $body->writeInt8($pdu->get(PDU::KEY_PRIORITY_FLAG));
                $body->writeDateTime(null);
                $body->writeDateTime(null);
                $body->writeInt8($pdu->get(PDU::KEY_REG_DELIVERY));
                $body->writeInt8(0);
                $body->writeInt8($pdu->get(PDU::KEY_DATA_CODING));
                $body->writeInt8(0);
                $body->writeInt8($pdu->get(PDU::KEY_SM_LENGTH));
                $body->writeString($pdu->get(PDU::KEY_SHORT_MESSAGE));
                break;
            case PDU::ID_SUBMIT_SM:
                $body->writeString($pdu->get(PDU::KEY_SERVICE_TYPE));
                $body->writeAddress($pdu->get(PDU::KEY_SRC_ADDRESS));
                $body->writeAddress($pdu->get(PDU::KEY_DST_ADDRESS));
                $body->writeInt8($pdu->get(PDU::KEY_ESM_CLASS));
                $body->writeInt8($pdu->get(PDU::KEY_PROTOCOL_ID));
                $body->writeInt8($pdu->get(PDU::KEY_PRIORITY_FLAG));
                $body->writeString($pdu->get(PDU::KEY_SCHEDULE_DELIVERY_TIME));//$body->writeDateTime($pdu->get(PDU::KEY_SCHEDULE_DELIVERY_TIME));
                $body->writeDateTime($pdu->get(PDU::KEY_VALIDITY_PERIOD));
                $body->writeInt8($pdu->get(PDU::KEY_REG_DELIVERY));
                $body->writeInt8($pdu->get(PDU::KEY_REPLACE_IF_PRESENT));
                $body->writeInt8($pdu->get(PDU::KEY_DATA_CODING));
                $body->writeInt8($pdu->get(PDU::KEY_SM_DEFAULT_MSG_ID));
                $body->writeInt8($pdu->get(PDU::KEY_SM_LENGTH));
                $body->writeString($pdu->get(PDU::KEY_SHORT_MESSAGE));
                break;
            case PDU::ID_DELIVER_SM_RESP:
                $body->writeString('');//<-- message_id = NULL
                break;
            case PDU::ID_SUBMIT_SM_RESP:
            case PDU::ID_DATA_SM_RESP:
                $body->writeString($pdu->get(PDU::KEY_MESSAGE_ID));
                break;
            case PDU::ID_REPLACE_SM:
                $body->writeString($pdu->get(PDU::KEY_MESSAGE_ID));
                $body->writeAddress($pdu->get(PDU::KEY_SRC_ADDRESS));
                $body->writeDateTime($pdu->get(PDU::KEY_SCHEDULE_DELIVERY_TIME));
                $body->writeDateTime($pdu->get(PDU::KEY_VALIDITY_PERIOD));
                $body->writeInt8($pdu->get(PDU::KEY_REG_DELIVERY));
                $body->writeInt8($pdu->get(PDU::KEY_SM_DEFAULT_MSG_ID));
                $body->writeInt8($pdu->get(PDU::KEY_SM_LENGTH));
                $body->writeString($pdu->get(PDU::KEY_SHORT_MESSAGE));
                break;
            case PDU::ID_DATA_SM:
                $body->writeString($pdu->get(PDU::KEY_SERVICE_TYPE));
                $body->writeAddress($pdu->get(PDU::KEY_SRC_ADDRESS));
                $body->writeAddress($pdu->get(PDU::KEY_DST_ADDRESS));
                $body->writeInt8($pdu->get(PDU::KEY_ESM_CLASS));
                $body->writeInt8($pdu->get(PDU::KEY_REG_DELIVERY));
                $body->writeInt8($pdu->get(PDU::KEY_DATA_CODING));
                break;
            default:
                throw new \UnexpectedValueException('Unexpected PDU id');
        }

        foreach ($pdu->getParams() as $key => $val) {
            if (!is_string($key)) {
                $body->writeTLV(new TLV($key, $val));
            }
        }

        $head->writeInt32(strlen($body) + 16);
        $head->writeInt32($pdu->getID());
        $head->writeInt32($pdu->getStatus());
        $head->writeInt32($pdu->getSeqNum());

        return $head . $body;
    }
}
