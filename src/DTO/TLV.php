<?php

namespace PE\Component\SMPP\DTO;

/**
 * @codeCoverageIgnore
 */
final class TLV
{
    public const TAG_DST_ADDRESS_SUBUNIT         = 0x0005;
    public const TAG_DST_NETWORK_TYPE            = 0x0006;
    public const TAG_DST_BEARER_TYPE             = 0x0007;
    public const TAG_DST_TELEMATICS_ID           = 0x0008;
    public const TAG_SRC_ADDR_SUBUNIT            = 0x000D;
    public const TAG_SRC_NETWORK_TYPE            = 0x000E;
    public const TAG_SRC_BEARER_TYPE             = 0x000F;
    public const TAG_SRC_TELEMATICS_ID           = 0x0010;
    public const TAG_QOS_TIME_TO_LIVE            = 0x0017;
    public const TAG_PAYLOAD_TYPE                = 0x0019;
    public const TAG_ADDITIONAL_STATUS_INFO_TEXT = 0x001D;
    public const TAG_RECEIPTED_MESSAGE_ID        = 0x001E;
    public const TAG_MS_MSG_WAIT_FACILITIES      = 0x0030;
    public const TAG_PRIVACY_INDICATOR           = 0x0201;
    public const TAG_SRC_SUB_ADDRESS             = 0x0202;
    public const TAG_DST_SUB_ADDRESS             = 0x0203;
    public const TAG_USER_MESSAGE_REFERENCE      = 0x0204;
    public const TAG_USER_RESPONSE_CODE          = 0x0205;
    public const TAG_SOURCE_PORT                 = 0x020A;
    public const TAG_DESTINATION_PORT            = 0x020B;
    public const TAG_SAR_MSG_REF_NUM             = 0x020C;
    public const TAG_LANGUAGE_INDICATOR          = 0x020D;
    public const TAG_SAR_TOTAL_SEGMENTS          = 0x020E;
    public const TAG_SAR_SEGMENT_SEQUENCE_NUM    = 0x020F;
    public const TAG_SC_INTERFACE_VERSION        = 0x0210;
    public const TAG_CALLBACK_NUM_PRES_IND       = 0x0302;
    public const TAG_CALLBACK_NUM_ATAG           = 0x0303;
    public const TAG_NUMBER_OF_MESSAGES          = 0x0304;
    public const TAG_CALLBACK_NUM                = 0x0381;
    public const TAG_DPF_RESULT                  = 0x0420;
    public const TAG_SET_DPF                     = 0x0421;
    public const TAG_MS_AVAILABILITY_STATUS      = 0x0422;
    public const TAG_NETWORK_ERROR_CODE          = 0x0423;
    public const TAG_MESSAGE_PAYLOAD             = 0x0424;
    public const TAG_DELIVERY_FAILURE_REASON     = 0x0425;
    public const TAG_MORE_MESSAGES_TO_SEND       = 0x0426;
    public const TAG_MESSAGE_STATUS              = 0x0427;
    public const TAG_USSD_SERVICE_OPERATION      = 0x0501;
    public const TAG_DISPLAY_TIME                = 0x1201;
    public const TAG_SMS_SIGNAL                  = 0x1203;
    public const TAG_MS_VALIDITY                 = 0x1204;
    public const TAG_ALERT_ON_MESSAGE_DELIVERY   = 0x130C;
    public const TAG_ITS_REPLY_TYPE              = 0x1380;
    public const TAG_ITS_SESSION_INFO            = 0x1383;

    private int $tag;

    /**
     * @var string|int|null
     */
    private $value;

    public function __construct(int $tag, $value = null)
    {
        $this->tag   = $tag;
        $this->value = $value;
    }

    public function getTag(): int
    {
        return $this->tag;
    }

    public function getValue()
    {
        return $this->value;
    }

    public static function TAG(): array
    {
        $constants = (new \ReflectionClass(__CLASS__))->getConstants();
        $constants = array_filter($constants, fn($name) => 0 === strpos($name, 'TAG_'), ARRAY_FILTER_USE_KEY);
        $constants = array_flip($constants);
        return array_map(fn($name) => substr($name, 4), $constants);
    }

    public function dump(): string
    {
        if (is_string($this->value)) {
            $val = "\"$this->value\"";
        } elseif (null === $this->value) {
            $val = 'NULL';
        } else {
            $val = $this->value;
        }

        return sprintf('TLV(tag: %s, val: %s)', self::TAG()[$this->tag] ?? sprintf('0x%04X', $this->tag), $val);
    }
}
