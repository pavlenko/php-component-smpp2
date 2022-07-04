<?php

namespace PE\SMPP;

interface CommandStatus
{
    public const NO_ERROR                     = 0x00000000;
    public const INVALID_MESSAGE_LENGTH       = 0x00000001;
    public const INVALID_COMMAND_LENGTH       = 0x00000002;
    public const INVALID_COMMAND_ID           = 0x00000003;
    public const INVALID_BIND_STATUS          = 0x00000004;
    public const ALREADY_BOUND                = 0x00000005;
    public const INVALID_PRIORITY_FLAG        = 0x00000006;
    public const INVALID_DELIVERY_FLAG        = 0x00000007;
    public const SYSTEM_ERROR                 = 0x00000008;
    public const INVALID_SRC_ADDRESS          = 0x0000000A;
    public const INVALID_DST_ADDRESS          = 0x0000000B;
    public const INVALID_MESSAGE_ID           = 0x0000000C;
    public const BIND_FAILED                  = 0x0000000D;
    public const INVALID_PASSWORD             = 0x0000000E;
    public const INVALID_SYSTEM_ID            = 0x0000000F;
    public const CANCEL_SM_FAILED             = 0x00000011;
    public const REPLACE_SM_FAILED            = 0x00000013;
    public const MESSAGE_QUEUE_FULL           = 0x00000014;
    public const INVALID_SERVICE_TYPE         = 0x00000015;
    public const INVALID_NUM_DESTINATIONS     = 0x00000033;
    public const INVALID_DIST_LIST_NAME       = 0x00000034;
    public const INVALID_DST_FLAG             = 0x00000040;
    public const INVALID_SUBMIT_W_REPLACE     = 0x00000042;
    public const INVALID_ESM_SUBMIT           = 0x00000043;
    public const CANNOT_SUBMIT_TO_DIST_LIST   = 0x00000044;
    public const SUBMIT_FAILED                = 0x00000045;
    public const INVALID_SRC_TON              = 0x00000048;
    public const INVALID_SRC_NPI              = 0x00000049;
    public const INVALID_DST_TON              = 0x00000050;
    public const INVALID_DST_NPI              = 0x00000051;
    public const INVALID_SYSTEM_TYPE          = 0x00000053;
    public const INVALID_REPLACE_FLAG         = 0x00000054;
    public const INVALID_NUM_MESSAGES         = 0x00000055;
    public const THROTTLED                    = 0x00000058;
    public const INVALID_SCHEDULE_TIME        = 0x00000061;
    public const INVALID_EXPIRY_TIME          = 0x00000062;
    public const INVALID_DEFINED_MESSAGE      = 0x00000063;
    public const RX_TEMPORARY_APPN            = 0x00000064;
    public const RX_PERMANENT_APPN            = 0x00000065;
    public const RX_REJECTED_APPN             = 0x00000066;
    public const QUERY_FAILED                 = 0x00000067;
    public const INVALID_OPTIONAL_PART        = 0x000000C0;
    public const OPTIONAL_PART_NOT_ALLOWED    = 0x000000C1;
    public const INVALID_PARAM_LENGTH         = 0x000000C2;
    public const MISSING_OPTIONAL_PARAM       = 0x000000C3;
    public const INVALID_OPTIONAL_PARAM_VALUE = 0x000000C4;
    public const DELIVERY_FAILURE             = 0x000000FE;
    public const UNKNOWN_ERROR                = 0x000000FF;
}
