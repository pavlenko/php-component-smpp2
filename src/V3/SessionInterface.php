<?php

namespace PE\Component\SMPP\V3;

// Only store session state, not read/send any PDU
// handle sequence number
interface SessionInterface
{
    public const STATUS_CREATED   = 0x0000;
    public const STATUS_OPENED    = 0x0001;
    public const STATUS_BOUND_TX  = 0x0010;
    public const STATUS_BOUND_RX  = 0x0100;
    public const STATUS_BOUND_TRX = 0x0110;
    public const STATUS_CLOSED    = 0x1000;
}
