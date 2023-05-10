<?php

namespace PE\Component\SMPP\DTO;

/**
 * @codeCoverageIgnore
 */
final class PDU
{
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
    public const STATUS_INVALID_MESSAGE_LENGTH       = 0x00000001;
    public const STATUS_INVALID_COMMAND_LENGTH       = 0x00000002;
    public const STATUS_INVALID_COMMAND_ID           = 0x00000003;
    public const STATUS_INVALID_BIND_STATUS          = 0x00000004;
    public const STATUS_ALREADY_BOUND                = 0x00000005;
    public const STATUS_INVALID_PRIORITY_FLAG        = 0x00000006;
    public const STATUS_INVALID_DELIVERY_FLAG        = 0x00000007;
    public const STATUS_SYSTEM_ERROR                 = 0x00000008;
    public const STATUS_INVALID_SRC_ADDRESS          = 0x0000000A;
    public const STATUS_INVALID_DST_ADDRESS          = 0x0000000B;
    public const STATUS_INVALID_MESSAGE_ID           = 0x0000000C;
    public const STATUS_BIND_FAILED                  = 0x0000000D;
    public const STATUS_INVALID_PASSWORD             = 0x0000000E;
    public const STATUS_INVALID_SYSTEM_ID            = 0x0000000F;
    public const STATUS_CANCEL_SM_FAILED             = 0x00000011;
    public const STATUS_REPLACE_SM_FAILED            = 0x00000013;
    public const STATUS_MESSAGE_QUEUE_FULL           = 0x00000014;
    public const STATUS_INVALID_SERVICE_TYPE         = 0x00000015;
    public const STATUS_INVALID_NUM_DESTINATIONS     = 0x00000033;
    public const STATUS_INVALID_DIST_LIST_NAME       = 0x00000034;
    public const STATUS_INVALID_DST_FLAG             = 0x00000040;
    public const STATUS_INVALID_SUBMIT_W_REPLACE     = 0x00000042;
    public const STATUS_INVALID_ESM_SUBMIT           = 0x00000043;
    public const STATUS_CANNOT_SUBMIT_TO_DIST_LIST   = 0x00000044;
    public const STATUS_SUBMIT_FAILED                = 0x00000045;
    public const STATUS_INVALID_SRC_TON              = 0x00000048;
    public const STATUS_INVALID_SRC_NPI              = 0x00000049;
    public const STATUS_INVALID_DST_TON              = 0x00000050;
    public const STATUS_INVALID_DST_NPI              = 0x00000051;
    public const STATUS_INVALID_SYSTEM_TYPE          = 0x00000053;
    public const STATUS_INVALID_REPLACE_FLAG         = 0x00000054;
    public const STATUS_INVALID_NUM_MESSAGES         = 0x00000055;
    public const STATUS_THROTTLED                    = 0x00000058;
    public const STATUS_INVALID_SCHEDULE_TIME        = 0x00000061;
    public const STATUS_INVALID_EXPIRY_TIME          = 0x00000062;
    public const STATUS_INVALID_DEFINED_MESSAGE      = 0x00000063;
    public const STATUS_RX_TEMPORARY_APP_ERR_CODE    = 0x00000064;
    public const STATUS_RX_PERMANENT_APP_ERR_CODE    = 0x00000065;
    public const STATUS_RX_REJECTED_APP_ERR_CODE     = 0x00000066;
    public const STATUS_QUERY_FAILED                 = 0x00000067;
    public const STATUS_INVALID_OPTIONAL_PART        = 0x000000C0;
    public const STATUS_OPTIONAL_PART_NOT_ALLOWED    = 0x000000C1;
    public const STATUS_INVALID_PARAM_LENGTH         = 0x000000C2;
    public const STATUS_MISSING_OPTIONAL_PARAM       = 0x000000C3;
    public const STATUS_INVALID_OPTIONAL_PARAM_VALUE = 0x000000C4;
    public const STATUS_DELIVERY_FAILURE             = 0x000000FE;
    public const STATUS_UNKNOWN_ERROR                = 0x000000FF;

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

    public function toLogger(): string
    {
        $identifiers = self::getIdentifiers();
        $statuses    = self::getStatuses();

        return sprintf(
            'PDU(%s, %s, %d)',
            $identifiers[$this->getID()] ?? sprintf('0x%08X', $this->getID()),
            $statuses[$this->getStatus()] ?? sprintf('0x%08X', $this->getStatus()),
            $this->getSeqNum()
        );
    }
}
