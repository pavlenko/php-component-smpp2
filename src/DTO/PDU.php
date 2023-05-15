<?php

namespace PE\Component\SMPP\DTO;

/**
 * @codeCoverageIgnore
 */
final class PDU
{
    public const KEY_SYSTEM_ID              = 'system_id';
    public const KEY_PASSWORD               = 'password';
    public const KEY_SYSTEM_TYPE            = 'system_type';
    public const KEY_INTERFACE_VERSION      = 'interface_version';
    public const KEY_ADDRESS                = 'address';
    public const KEY_ESME_ADDRESS           = 'esme_address';
    public const KEY_SRC_ADDRESS            = 'src_address';
    public const KEY_DST_ADDRESS            = 'dst_address';
    public const KEY_SERVICE_TYPE           = 'service_type';
    public const KEY_ESM_CLASS              = 'esm_class';
    public const KEY_PROTOCOL_ID            = 'protocol_id';
    public const KEY_PRIORITY_FLAG          = 'priority_flag';
    public const KEY_SCHEDULED_AT           = 'scheduled_at';
    public const KEY_VALIDITY_PERIOD        = 'validity_period';
    public const KEY_REG_DELIVERY           = 'reg_delivery';
    public const KEY_REPLACE_IF_PRESENT     = 'replace_if_present_flag';
    public const KEY_DATA_CODING            = 'data_coding';
    public const KEY_SM_DEFAULT_MSG_ID      = 'sm_default_msg_id';
    public const KEY_SM_LENGTH              = 'sm_length';
    public const KEY_SHORT_MESSAGE          = 'short_message';
    public const KEY_MESSAGE_ID             = 'message_id';
    public const KEY_NUMBER_OF_DESTS        = 'number_of_dests';
    public const KEY_DEST_FLAG              = 'dest_flag';
    public const KEY_NO_UNSUCCESS           = 'no_unsuccess';
    public const KEY_DL_NAME                = 'dl_name';
    public const KEY_MESSAGE_STATE          = 'message_state';
    public const KEY_ERROR_CODE             = 'error_code';
    public const KEY_FINAL_DATE             = 'final_date';

    public const ALLOWED_TLV_BY_ID = [
        self::ID_BIND_RECEIVER_RESP => [
            TLV::TAG_SC_INTERFACE_VERSION,
        ],
        self::ID_BIND_TRANSMITTER_RESP => [
            TLV::TAG_SC_INTERFACE_VERSION,
        ],
        self::ID_BIND_TRANSCEIVER_RESP => [
            TLV::TAG_SC_INTERFACE_VERSION,
        ],
        self::ID_SUBMIT_SM => [
            TLV::TAG_USER_MESSAGE_REFERENCE,
            TLV::TAG_SOURCE_PORT,
            TLV::TAG_SOURCE_ADDR_SUBUNIT,
            TLV::TAG_DESTINATION_PORT,
            TLV::TAG_DEST_ADDR_SUBUNIT,
            TLV::TAG_SAR_MSG_REF_NUM,
            TLV::TAG_SAR_TOTAL_SEGMENTS,
            TLV::TAG_SAR_SEGMENT_SEQNUM,
            TLV::TAG_MORE_MESSAGES_TO_SEND,
            TLV::TAG_PAYLOAD_TYPE,
            TLV::TAG_MESSAGE_PAYLOAD,
            TLV::TAG_PRIVACY_INDICATOR,
            TLV::TAG_CALLBACK_NUM,
            TLV::TAG_CALLBACK_NUM_PRES_IND,
            TLV::TAG_CALLBACK_NUM_ATAG,
            TLV::TAG_SOURCE_SUBADDRESS,
            TLV::TAG_DEST_SUBADDRESS,
            TLV::TAG_USER_RESPONSE_CODE,
            TLV::TAG_DISPLAY_TIME,
            TLV::TAG_SMS_SIGNAL,
            TLV::TAG_MS_VALIDITY,
            TLV::TAG_MS_MSG_WAIT_FACILITIES,
            TLV::TAG_NUMBER_OF_MESSAGES,
            TLV::TAG_ALERT_ON_MESSAGE_DELIVERY,
            TLV::TAG_LANGUAGE_INDICATOR,
            TLV::TAG_ITS_REPLY_TYPE,
            TLV::TAG_ITS_SESSION_INFO,
            TLV::TAG_USSD_SERVICE_OP,
        ],
        self::ID_DELIVER_SM => [
            TLV::TAG_USER_MESSAGE_REFERENCE,
            TLV::TAG_SOURCE_PORT,
            TLV::TAG_DESTINATION_PORT,
            TLV::TAG_SAR_MSG_REF_NUM,
            TLV::TAG_SAR_TOTAL_SEGMENTS,
            TLV::TAG_SAR_SEGMENT_SEQNUM,
            TLV::TAG_USER_RESPONSE_CODE,
            TLV::TAG_PRIVACY_INDICATOR,
            TLV::TAG_PAYLOAD_TYPE,
            TLV::TAG_MESSAGE_PAYLOAD,
            TLV::TAG_CALLBACK_NUM,
            TLV::TAG_SOURCE_SUBADDRESS,
            TLV::TAG_DEST_SUBADDRESS,
            TLV::TAG_LANGUAGE_INDICATOR,
            TLV::TAG_ITS_SESSION_INFO,
            TLV::TAG_NETWORK_ERROR_CODE,
            TLV::TAG_MESSAGE_STATE,
            TLV::TAG_RECEIPTED_MESSAGE_ID,
        ],
        self::ID_DATA_SM => [
            TLV::TAG_SOURCE_PORT,
            TLV::TAG_SOURCE_ADDR_SUBUNIT,
            TLV::TAG_SOURCE_NETWORK_TYPE,
            TLV::TAG_SOURCE_BEARER_TYPE,
            TLV::TAG_SOURCE_TELEMATICS_ID,
            TLV::TAG_DESTINATION_PORT,
            TLV::TAG_DEST_ADDR_SUBUNIT,
            TLV::TAG_DEST_NETWORK_TYPE,
            TLV::TAG_DEST_BEARER_TYPE,
            TLV::TAG_DEST_TELEMATICS_ID,
            TLV::TAG_SAR_MSG_REF_NUM,
            TLV::TAG_SAR_TOTAL_SEGMENTS,
            TLV::TAG_SAR_SEGMENT_SEQNUM,
            TLV::TAG_MORE_MESSAGES_TO_SEND,
            TLV::TAG_QOS_TIME_TO_LIVE,
            TLV::TAG_PAYLOAD_TYPE,
            TLV::TAG_MESSAGE_PAYLOAD,
            TLV::TAG_SET_DPF,
            TLV::TAG_RECEIPTED_MESSAGE_ID,
            TLV::TAG_MESSAGE_STATE,
            TLV::TAG_NETWORK_ERROR_CODE,
            TLV::TAG_USER_MESSAGE_REFERENCE,
            TLV::TAG_PRIVACY_INDICATOR,
            TLV::TAG_CALLBACK_NUM,
            TLV::TAG_CALLBACK_NUM_PRES_IND,
            TLV::TAG_CALLBACK_NUM_ATAG,
            TLV::TAG_SOURCE_SUBADDRESS,
            TLV::TAG_DEST_SUBADDRESS,
            TLV::TAG_USER_RESPONSE_CODE,
            TLV::TAG_DISPLAY_TIME,
            TLV::TAG_SMS_SIGNAL,
            TLV::TAG_MS_VALIDITY,
            TLV::TAG_MS_MSG_WAIT_FACILITIES,
            TLV::TAG_NUMBER_OF_MESSAGES,
            TLV::TAG_ALERT_ON_MESSAGE_DELIVERY,
            TLV::TAG_LANGUAGE_INDICATOR,
            TLV::TAG_ITS_REPLY_TYPE,
            TLV::TAG_ITS_SESSION_INFO,
        ],
        PDU::ID_DATA_SM_RESP => [
            TLV::TAG_DELIVERY_FAILURE_REASON,
            TLV::TAG_NETWORK_ERROR_CODE,
            TLV::TAG_ADDITIONAL_STATUS_INFO_TEXT,
            TLV::TAG_DPF_RESULT,
        ],
        PDU::ID_ALERT_NOTIFICATION => [
            TLV::TAG_MS_AVAILABILITY_STATUS,
        ],
    ];

    public const ID_GENERIC_NACK          = 0x80_00_00_00;//<-- this is response
    public const ID_BIND_RECEIVER         = 0x00_00_00_01;
    public const ID_BIND_RECEIVER_RESP    = 0x80_00_00_01;
    public const ID_BIND_TRANSMITTER      = 0x00_00_00_02;
    public const ID_BIND_TRANSMITTER_RESP = 0x80_00_00_02;
    public const ID_QUERY_SM              = 0x00_00_00_03;
    public const ID_QUERY_SM_RESP         = 0x80_00_00_03;
    public const ID_SUBMIT_SM             = 0x00_00_00_04;
    public const ID_SUBMIT_SM_RESP        = 0x80_00_00_04;
    public const ID_DELIVER_SM            = 0x00_00_00_05;
    public const ID_DELIVER_SM_RESP       = 0x80_00_00_05;
    public const ID_UNBIND                = 0x00_00_00_06;
    public const ID_UNBIND_RESP           = 0x80_00_00_06;
    public const ID_REPLACE_SM            = 0x00_00_00_07;
    public const ID_REPLACE_SM_RESP       = 0x80_00_00_07;
    public const ID_CANCEL_SM             = 0x00_00_00_08;
    public const ID_CANCEL_SM_RESP        = 0x80_00_00_08;
    public const ID_BIND_TRANSCEIVER      = 0x00_00_00_09;
    public const ID_BIND_TRANSCEIVER_RESP = 0x80_00_00_09;
    public const ID_OUT_BIND              = 0x00_00_00_0B;//<-- no direct response, must reply with bind receiver
    public const ID_ENQUIRE_LINK          = 0x00_00_00_15;
    public const ID_ENQUIRE_LINK_RESP     = 0x80_00_00_15;
    public const ID_SUBMIT_MULTI          = 0x00_00_00_21;//<-- this is complex so implement later
    public const ID_SUBMIT_MULTI_RESP     = 0x80_00_00_21;//<-- this is complex so implement later
    public const ID_ALERT_NOTIFICATION    = 0x00_00_01_02;
    public const ID_DATA_SM               = 0x00_00_01_03;
    public const ID_DATA_SM_RESP          = 0x80_00_01_03;

    public const STATUS_NO_ERROR                     = 0x00000000;
    public const STATUS_INVALID_MESSAGE_LENGTH       = 0x00000001;//TODO if passed but not match real length
    public const STATUS_INVALID_COMMAND_LENGTH       = 0x00000002;
    public const STATUS_INVALID_COMMAND_ID           = 0x00000003;
    public const STATUS_INVALID_BIND_STATUS          = 0x00000004;
    public const STATUS_ALREADY_BOUND                = 0x00000005;
    public const STATUS_INVALID_PRIORITY_FLAG        = 0x00000006;//TODO if passed but not in constants
    public const STATUS_INVALID_DELIVERY_FLAG        = 0x00000007;//TODO if passed but not in constants
    public const STATUS_SYSTEM_ERROR                 = 0x00000008;
    public const STATUS_INVALID_SRC_ADDRESS          = 0x0000000A;//TODO if passed but not valid
    public const STATUS_INVALID_DST_ADDRESS          = 0x0000000B;//TODO if passed but not valid
    public const STATUS_INVALID_MESSAGE_ID           = 0x0000000C;//TODO if passed but malformed
    public const STATUS_BIND_FAILED                  = 0x0000000D;//TODO if for some reasons failed (by user code?)
    public const STATUS_INVALID_PASSWORD             = 0x0000000E;//TODO if passed but malformed ot not match
    public const STATUS_INVALID_SYSTEM_ID            = 0x0000000F;//TODO if passed but malformed
    public const STATUS_CANCEL_SM_FAILED             = 0x00000011;
    public const STATUS_REPLACE_SM_FAILED            = 0x00000013;
    public const STATUS_MESSAGE_QUEUE_FULL           = 0x00000014;
    public const STATUS_INVALID_SERVICE_TYPE         = 0x00000015;//TODO if passed but not in constants
    public const STATUS_INVALID_NUM_DESTINATIONS     = 0x00000033;
    public const STATUS_INVALID_DL_NAME              = 0x00000034;
    public const STATUS_INVALID_DST_FLAG             = 0x00000040;
    public const STATUS_INVALID_SUBMIT_W_REPLACE     = 0x00000042;
    public const STATUS_INVALID_ESM_CLASS            = 0x00000043;
    public const STATUS_CANNOT_SUBMIT_TO_DIST_LIST   = 0x00000044;
    public const STATUS_SUBMIT_SM_FAILED             = 0x00000045;
    public const STATUS_INVALID_SRC_TON              = 0x00000048;//TODO if passed but not in constants
    public const STATUS_INVALID_SRC_NPI              = 0x00000049;//TODO if passed but not in constants
    public const STATUS_INVALID_DST_TON              = 0x00000050;//TODO if passed but not in constants
    public const STATUS_INVALID_DST_NPI              = 0x00000051;//TODO if passed but not in constants
    public const STATUS_INVALID_SYSTEM_TYPE          = 0x00000053;//TODO if passed but not in constants
    public const STATUS_INVALID_REPLACE_FLAG         = 0x00000054;//TODO if passed but not in constants
    public const STATUS_INVALID_NUM_MESSAGES         = 0x00000055;
    public const STATUS_THROTTLED                    = 0x00000058;
    public const STATUS_INVALID_SCHEDULE_TIME        = 0x00000061;//TODO if passed but malformed
    public const STATUS_INVALID_EXPIRY_TIME          = 0x00000062;//TODO if passed but malformed
    public const STATUS_INVALID_DEFINED_MESSAGE      = 0x00000063;
    public const STATUS_RX_TEMPORARY_APP_ERR_CODE    = 0x00000064;
    public const STATUS_RX_PERMANENT_APP_ERR_CODE    = 0x00000065;
    public const STATUS_RX_REJECTED_APP_ERR_CODE     = 0x00000066;
    public const STATUS_QUERY_SM_FAILED              = 0x00000067;
    public const STATUS_INVALID_OPTIONAL_PART        = 0x000000C0;//TODO if cannot decode TLV
    public const STATUS_OPTIONAL_PARAM_NOT_ALLOWED   = 0x000000C1;//TODO if TLV not allowed for specific PDU ID
    public const STATUS_INVALID_PARAM_LENGTH         = 0x000000C2;
    public const STATUS_MISSING_OPTIONAL_PARAM       = 0x000000C3;
    public const STATUS_INVALID_OPTIONAL_PARAM_VALUE = 0x000000C4;
    public const STATUS_DELIVERY_FAILURE             = 0x000000FE;
    public const STATUS_UNKNOWN_ERROR                = 0x000000FF;

    public const SERVICE_TYPE_NONE = null;
    public const SERVICE_TYPE_CMT  = 'CMT';
    public const SERVICE_TYPE_CPT  = 'CPT';
    public const SERVICE_TYPE_VMN  = 'VMN';
    public const SERVICE_TYPE_VMA  = 'VMA';
    public const SERVICE_TYPE_WAP  = 'WAP';
    public const SERVICE_TYPE_USSD = 'USSD';

    public const ESM_MSG_MODE_DEFAULT    = 0b00_00_00_00;//esme->smsc
    public const ESM_MSG_MODE_DATAGRAM   = 0b00_00_00_01;//esme->smsc
    public const ESM_MSG_MODE_FORWARD    = 0b00_00_00_10;//esme->smsc
    public const ESM_MSG_MODE_STORED     = 0b00_00_00_11;//esme->smsc

    public const ESM_MSG_TYPE_DEFAULT              = 0b00_00_00_00;//both
    public const ESM_MSG_TYPE_HAS_DELIVERY_RECEIPT = 0b00_00_01_00;//smsc->esme
    public const ESM_MSG_TYPE_HAS_ACK_AUTO         = 0b00_00_10_00;//both
    public const ESM_MSG_TYPE_HAS_ACK_MANUAL       = 0b00_01_00_00;//both
    public const ESM_MSG_TYPE_HAS_DELIVERY_NOTIFY  = 0b00_10_00_00;//smsc->esme

    public const ESM_SPECIAL_NONE        = 0b00_00_00_00;//both
    public const ESM_SPECIAL_UDHI        = 0b01_00_00_00;//both
    public const ESM_SPECIAL_REPLY_PATH  = 0b10_00_00_00;//both
    public const ESM_SPECIAL_BOTH        = 0b11_00_00_00;//both

    public const PRIORITY_DEFAULT   = 0;
    public const PRIORITY_HIGH      = 1;
    public const PRIORITY_URGENT    = 2;
    public const PRIORITY_EMERGENCY = 3;

    public const REG_DELIVERY_SMSC_NO          = 0b00_00_00_00;
    public const REG_DELIVERY_SMSC_YES         = 0b00_00_00_01;
    public const REG_DELIVERY_ESME_NO          = 0b00_00_00_00;
    public const REG_DELIVERY_ESME_AUTO        = 0b00_00_01_00;
    public const REG_DELIVERY_ESME_MANUAL      = 0b00_00_10_00;
    public const REG_DELIVERY_ESME_BOTH        = 0b00_00_11_00;
    public const REG_DELIVERY_INTERMEDIATE_NO  = 0b00_00_00_00;
    public const REG_DELIVERY_INTERMEDIATE_YES = 0b00_01_00_00;

    public const DATA_CODING_DEFAULT      = 0;
    public const DATA_CODING_IA5          = 1; // IA5 (CCITT T.50)/ASCII (ANSI X3.4)
    public const DATA_CODING_BINARY_ALIAS = 2;
    public const DATA_CODING_ISO8859_1    = 3; // Latin 1
    public const DATA_CODING_BINARY       = 4;
    public const DATA_CODING_JIS          = 5;
    public const DATA_CODING_ISO8859_5    = 6; // Cyrillic
    public const DATA_CODING_ISO8859_8    = 7; // Latin/Hebrew
    public const DATA_CODING_UCS2         = 8; // UCS-2BE (Big Endian)
    public const DATA_CODING_PICTOGRAM    = 9;
    public const DATA_CODING_ISO2022_JP   = 10; // Music codes
    public const DATA_CODING_KANJI        = 13; // Extended Kanji JIS
    public const DATA_CODING_KSC5601      = 14;

    private int $id;
    private int $status;
    private int $seqNum;
    private array $params;

    //TODO add required(string $key, $type) method
    //TODO add optional(string $key, $type, $default) method
    //TODO remove status arg, create setter instead???
    public function __construct(int $id, int $status, int $seqNum, array $params = [])
    {
        $this->id     = $id;
        $this->status = $status;
        $this->seqNum = $seqNum;
        $this->params = $params;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getSeqNum(): int
    {
        return $this->seqNum;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->params);
    }

    //TODO add helper methods for return typed result
    public function get(string $name, $default = null)
    {
        return $this->has($name) ? $this->params[$name] : $default;
    }

    public function set(string $name, $value): void
    {
        $this->params[$name] = $value;
    }

    public static function getIdentifiers(): array
    {
        $constants = (new \ReflectionClass(__CLASS__))->getConstants();
        $constants = array_filter($constants, fn($name) => 0 === strpos($name, 'ID_'), ARRAY_FILTER_USE_KEY);
        $constants = array_flip($constants);
        return array_map(fn($name) => substr($name, 3), $constants);
    }

    public static function getStatuses(): array
    {
        $constants = (new \ReflectionClass(__CLASS__))->getConstants();
        $constants = array_filter($constants, fn($name) => 0 === strpos($name, 'STATUS_'), ARRAY_FILTER_USE_KEY);
        $constants = array_flip($constants);
        return array_map(fn($name) => substr($name, 7), $constants);
    }

    //TODO maybe change to toString and create full formatted string dump with all params for debug
    //TODO maybe create special dumper class
    public function toLogger(): string
    {
        $identifiers = self::getIdentifiers();
        $statuses    = self::getStatuses();

        $params = [];
        $length = 0;
        foreach ($this->params as $key => $val) {
            if (is_numeric($key)) {
                $key = TLV::TAG()[$key] ?? sprintf('0x%08X', $key);
                $val = $val instanceof TLV ? $val->dump() : 'NULL';
            } elseif ($val instanceof Address || $val instanceof DateTime) {
                $val = $val->dump();
            } elseif (null === $val) {
                $val = 'NULL';
            } elseif (is_string($val)) {
                $val = "\"$val\"";
            }
            $length = max($length, strlen($key));
            $params[$key] = $val;
        }

        $body = '';
        foreach ($params as $key => $val) {
            $body .= '    ' . str_pad($key, $length) . ' :' . $val . "\n";
        }

        if ('' !== $body) {
            $body = "\n" . $body;
        }

        return sprintf(
            'PDU(id: %s, status: %s, seq: %d, params: [%s])',
            $identifiers[$this->getID()] ?? sprintf('0x%08X', $this->getID()),
            $statuses[$this->getStatus()] ?? sprintf('0x%08X', $this->getStatus()),
            $this->getSeqNum(),
            $body
        );
    }
}
