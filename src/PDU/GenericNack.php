<?php

namespace PE\Component\SMPP\PDU;

final class GenericNack extends PDU
{
    public function getCommandID(): int
    {
        return 0x80000000;
    }
}
