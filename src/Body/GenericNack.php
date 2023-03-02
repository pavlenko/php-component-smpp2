<?php

namespace PE\Component\SMPP\Body;

final class GenericNack extends PDU
{
    public function getCommandID(): int
    {
        return 0x80000000;
    }
}
