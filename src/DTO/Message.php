<?php

namespace PE\Component\SMPP\DTO;

final class Message
{
    public const ENROUTE       = 1;
    public const DELIVERED     = 2;
    public const EXPIRED       = 3;
    public const DELETED       = 4;
    public const UNDELIVERABLE = 5;
    public const ACCEPTED      = 6;
    public const UNKNOWN       = 7;
    public const REJECTED      = 8;
}
