<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Util\Buffer;

class Serializer implements SerializerInterface
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
                try {
                    $params['sc_interface_version'] = $buffer->shiftTLV();
                } catch (\Throwable $e) { /* DO NOTHING */ }
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
                try {
                    $params['ms_availability_status'] = $buffer->shiftTLV();
                } catch (\Throwable $e) { /* DO NOTHING */ }
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
                    'schedule_delivery_time'  => $buffer->shiftDateTime(),
                    'validity_period'         => $buffer->shiftDateTime(),
                    'registered_delivery'     => $buffer->shiftInt8(),
                    'replace_if_present_flag' => $buffer->shiftInt8(),
                    'data_coding'             => $buffer->shiftInt8(),
                    'sm_default_msg_id'       => $buffer->shiftInt8(),
                    'sm_length'               => $buffer->shiftInt8(),
                    'short_message'           => $buffer->shiftString(254)
                ];

                while (true) {
                    try {
                        $tlv = $buffer->shiftTLV();
                        $params[$tlv->getTag()] = $tlv->getValue();
                    } catch (\Throwable $e) {
                        break;
                    }
                }
                /*
                try {
                    $params['user_message_reference'] = $buffer->shiftTLV();
                    $params['source_port']            = $buffer->shiftTLV();
                    $params['source_addr_subunit']    = $buffer->shiftTLV();
                    $params['destination_port']       = $buffer->shiftTLV();
                    $params['dest_addr_subunit']      = $buffer->shiftTLV();
                    $params['sar_msg_ref_num']        = $buffer->shiftTLV();
                    $params['sar_total_segments']     = $buffer->shiftTLV();
                    $params['sar_segment_seqnum']     = $buffer->shiftTLV();
                    $params['payload_type']           = $buffer->shiftTLV();
                    $params['message_payload']        = $buffer->shiftTLV();
                    $params['privacy_indicator']      = $buffer->shiftTLV();
                    $params['callback_num']           = $buffer->shiftTLV();
                    $params['callback_num_pres_ind']  = $buffer->shiftTLV();
                    $params['callback_num_atag']      = $buffer->shiftTLV();
                    $params['source_subaddress']      = $buffer->shiftTLV();
                    $params['dest_subaddress']        = $buffer->shiftTLV();
                    $params['user_response_code']     = $buffer->shiftTLV();
                    $params['display_time']           = $buffer->shiftTLV();
                    $params['sms_signal']             = $buffer->shiftTLV();
                    $params['ms_validity']            = $buffer->shiftTLV();
                    $params['ms_msg_wait_facilities'] = $buffer->shiftTLV();
                    $params['number_of_messages']     = $buffer->shiftTLV();
                    $params['alert_on_msg_delivery']  = $buffer->shiftTLV();
                    $params['language_indicator']     = $buffer->shiftTLV();
                    $params['its_reply_type']         = $buffer->shiftTLV();
                    $params['its_session_info']       = $buffer->shiftTLV();
                    $params['ussd_service_op']        = $buffer->shiftTLV();
                    $params['language_indicator']     = $buffer->shiftTLV();
                } catch (\Throwable $e) {}
                */
                break;


            case PDUInterface::ID_DELIVER_SM_RESP:
            case PDUInterface::ID_SUBMIT_SM_RESP:
                $params = ['message_id' => $buffer->shiftString(65)];
                break;
            default:
                throw new \UnexpectedValueException('Unexpected PDU id');
        }

        //TODO read tlv here, because common parse logic

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
                if ($pdu->has('sc_interface_version')) {
                    $body->writeTLV($pdu->get('sc_interface_version'));
                }
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
                if ($pdu->has('ms_availability_status')) {
                    $body->writeTLV($pdu->get('ms_availability_status'));
                }
                break;
            case PDUInterface::ID_DELIVER_SM_RESP:
            case PDUInterface::ID_SUBMIT_SM_RESP:
                $body->writeString($pdu->get('message_id'));
                break;
            default:
                throw new \UnexpectedValueException('Unexpected PDU id');
        }

        $head->writeInt32(strlen($body) + 16);
        $head->writeInt32($pdu->getID());
        $head->writeInt32($pdu->getStatus());
        $head->writeInt32($pdu->getSeqNum());

        return $head . $body;
    }
}
