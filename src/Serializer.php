<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\DTO\PDUInterface;
use PE\Component\SMPP\DTO\TLV;
use PE\Component\SMPP\Util\Buffer;

final class Serializer implements SerializerInterface
{
    public function decode(string $pdu): PDUInterface
    {
        $buffer = new Buffer($pdu);
        $id     = $buffer->shiftInt32();
        $status = $buffer->shiftInt32();
        $seqNum = $buffer->shiftInt32();

        switch ($id) {
            case PDUInterface::ID_GENERIC_NACK:
            case PDUInterface::ID_QUERY_SM:
            case PDUInterface::ID_QUERY_SM_RESP:
            case PDUInterface::ID_UNBIND:
            case PDUInterface::ID_UNBIND_RESP:
            case PDUInterface::ID_ENQUIRE_LINK:
            case PDUInterface::ID_ENQUIRE_LINK_RESP:
            case PDUInterface::ID_CANCEL_SM_RESP:
            case PDUInterface::ID_REPLACE_SM_RESP:
                /* DO NOTHING JUST KNOWN ID */
                break;
            case PDUInterface::ID_BIND_RECEIVER:
            case PDUInterface::ID_BIND_TRANSMITTER:
            case PDUInterface::ID_BIND_TRANSCEIVER:
                $params = [
                    'system_id'         => $buffer->shiftString(16),
                    'password'          => $buffer->shiftString(9),
                    'system_type'       => $buffer->shiftString(13),
                    'interface_version' => $buffer->shiftInt8(),
                    'address'           => $buffer->shiftAddress(41),
                ];
                break;
            case PDUInterface::ID_BIND_RECEIVER_RESP:
            case PDUInterface::ID_BIND_TRANSMITTER_RESP:
            case PDUInterface::ID_BIND_TRANSCEIVER_RESP:
                $params = ['system_id' => $buffer->shiftString(16)];
                break;
            case PDUInterface::ID_CANCEL_SM:
                $params = [
                    'service_type'   => $buffer->shiftString(6),
                    'message_id'     => $buffer->shiftString(16),
                    'source_address' => $buffer->shiftAddress(21),
                    'dest_address'   => $buffer->shiftAddress(21),
                ];
                break;
            case PDUInterface::ID_OUT_BIND:
                $params = [
                    'system_id' => $buffer->shiftString(16),
                    'password'  => $buffer->shiftString(9),
                ];
                break;
            case PDUInterface::ID_ALERT_NOTIFICATION:
                $params = [
                    'source_address' => $buffer->shiftAddress(65),
                    'esme_address'   => $buffer->shiftAddress(65),
                ];
                break;
            case PDUInterface::ID_DELIVER_SM:
            case PDUInterface::ID_SUBMIT_SM:
                $params = [
                    'service_type'            => $buffer->shiftString(6),
                    'source_address'          => $buffer->shiftAddress(21),
                    'dest_address'            => $buffer->shiftAddress(21),
                    'esm_class'               => $buffer->shiftInt8(),
                    'protocol_id'             => $buffer->shiftInt8(),
                    'priority_flag'           => $buffer->shiftInt8(),
                    'schedule_delivery_time'  => $buffer->shiftDateTime(),//->NULL ID_DELIVER_SM
                    'validity_period'         => $buffer->shiftDateTime(),//->NULL ID_DELIVER_SM
                    'registered_delivery'     => $buffer->shiftInt8(),
                    'replace_if_present_flag' => $buffer->shiftInt8(),//->NULL ID_DELIVER_SM
                    'data_coding'             => $buffer->shiftInt8(),
                    'sm_default_msg_id'       => $buffer->shiftInt8(),//->NULL ID_DELIVER_SM
                    'sm_length'               => $buffer->shiftInt8(),
                    'short_message'           => $buffer->shiftString(254),
                ];
                break;
            case PDUInterface::ID_DELIVER_SM_RESP:
            case PDUInterface::ID_SUBMIT_SM_RESP:
            case PDUInterface::ID_DATA_SM_RESP:
                $params = ['message_id' => $buffer->shiftString(65)];
                break;
            case PDUInterface::ID_REPLACE_SM:
                $params = [
                    'message_id'              => $buffer->shiftString(65),
                    'source_address'          => $buffer->shiftAddress(21),
                    'schedule_delivery_time'  => $buffer->shiftDateTime(),
                    'validity_period'         => $buffer->shiftDateTime(),
                    'registered_delivery'     => $buffer->shiftInt8(),
                    'sm_default_msg_id'       => $buffer->shiftInt8(),
                    'sm_length'               => $buffer->shiftInt8(),
                    'short_message'           => $buffer->shiftString(254),
                ];
                break;
            case PDUInterface::ID_DATA_SM:
                $params = [
                    'service_type'        => $buffer->shiftString(6),
                    'source_address'      => $buffer->shiftAddress(21),
                    'dest_address'        => $buffer->shiftAddress(21),
                    'esm_class'           => $buffer->shiftInt8(),
                    'registered_delivery' => $buffer->shiftInt8(),
                    'data_coding'         => $buffer->shiftInt8(),
                ];
                break;
            default:
                throw new \UnexpectedValueException('Unexpected PDU id');
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

    public function encode(PDUInterface $pdu): string
    {
        $head = new Buffer();
        $body = new Buffer();

        switch ($pdu->getID()) {
            case PDUInterface::ID_GENERIC_NACK:
            case PDUInterface::ID_QUERY_SM:
            case PDUInterface::ID_QUERY_SM_RESP:
            case PDUInterface::ID_UNBIND:
            case PDUInterface::ID_UNBIND_RESP:
            case PDUInterface::ID_ENQUIRE_LINK:
            case PDUInterface::ID_ENQUIRE_LINK_RESP:
            case PDUInterface::ID_CANCEL_SM_RESP:
            case PDUInterface::ID_REPLACE_SM_RESP:
                /* DO NOTHING JUST KNOWN ID */
                break;
            case PDUInterface::ID_BIND_RECEIVER:
            case PDUInterface::ID_BIND_TRANSMITTER:
            case PDUInterface::ID_BIND_TRANSCEIVER:
                $body->writeString($pdu->get('system_id'));
                $body->writeString($pdu->get('password'));
                $body->writeString($pdu->get('system_type'));
                $body->writeInt8($pdu->get('interface_version'));
                $body->writeAddress($pdu->get('address'));
                break;
            case PDUInterface::ID_BIND_RECEIVER_RESP:
            case PDUInterface::ID_BIND_TRANSMITTER_RESP:
            case PDUInterface::ID_BIND_TRANSCEIVER_RESP:
                $body->writeString($pdu->get('system_id'));
                break;
            case PDUInterface::ID_CANCEL_SM:
                $body->writeString($pdu->get('service_type'));
                $body->writeString($pdu->get('message_id'));
                $body->writeAddress($pdu->get('source_address'));
                $body->writeAddress($pdu->get('dest_address'));
                break;
            case PDUInterface::ID_OUT_BIND:
                $body->writeString($pdu->get('system_id'));
                $body->writeString($pdu->get('password'));
                break;
            case PDUInterface::ID_ALERT_NOTIFICATION:
                $body->writeAddress($pdu->get('source_address'));
                $body->writeAddress($pdu->get('esme_address'));
                break;
            case PDUInterface::ID_DELIVER_SM:
                $body->writeString($pdu->get('service_type'));
                $body->writeAddress($pdu->get('source_address'));
                $body->writeAddress($pdu->get('dest_address'));
                $body->writeInt8($pdu->get('esm_class'));
                $body->writeInt8($pdu->get('protocol_id'));
                $body->writeInt8($pdu->get('priority_flag'));
                $body->writeDateTime(null);
                $body->writeDateTime(null);
                $body->writeInt8($pdu->get('registered_delivery'));
                $body->writeInt8(0);
                $body->writeInt8($pdu->get('data_coding'));
                $body->writeInt8(0);
                $body->writeInt8($pdu->get('sm_length'));
                $body->writeString($pdu->get('short_message'));
                break;
            case PDUInterface::ID_SUBMIT_SM:
                $body->writeString($pdu->get('service_type'));
                $body->writeAddress($pdu->get('source_address'));
                $body->writeAddress($pdu->get('dest_address'));
                $body->writeInt8($pdu->get('esm_class'));
                $body->writeInt8($pdu->get('protocol_id'));
                $body->writeInt8($pdu->get('priority_flag'));
                $body->writeDateTime($pdu->get('schedule_delivery_time'));
                $body->writeDateTime($pdu->get('validity_period'));
                $body->writeInt8($pdu->get('registered_delivery'));
                $body->writeInt8($pdu->get('replace_if_present_flag'));
                $body->writeInt8($pdu->get('data_coding'));
                $body->writeInt8($pdu->get('sm_default_msg_id'));
                $body->writeInt8($pdu->get('sm_length'));
                $body->writeString($pdu->get('short_message'));
                break;
            case PDUInterface::ID_DELIVER_SM_RESP:
                $body->writeString('');//<-- message_id = NULL
                break;
            case PDUInterface::ID_SUBMIT_SM_RESP:
            case PDUInterface::ID_DATA_SM_RESP:
                $body->writeString($pdu->get('message_id'));
                break;
            case PDUInterface::ID_REPLACE_SM:
                $body->writeString($pdu->get('message_id'));
                $body->writeAddress($pdu->get('source_address'));
                $body->writeDateTime($pdu->get('schedule_delivery_time'));
                $body->writeDateTime($pdu->get('validity_period'));
                $body->writeInt8($pdu->get('registered_delivery'));
                $body->writeInt8($pdu->get('sm_default_msg_id'));
                $body->writeInt8($pdu->get('sm_length'));
                $body->writeString($pdu->get('short_message'));
                break;
            case PDUInterface::ID_DATA_SM:
                $body->writeString($pdu->get('service_type'));
                $body->writeAddress($pdu->get('source_address'));
                $body->writeAddress($pdu->get('dest_address'));
                $body->writeInt8($pdu->get('esm_class'));
                $body->writeInt8($pdu->get('registered_delivery'));
                $body->writeInt8($pdu->get('data_coding'));
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
