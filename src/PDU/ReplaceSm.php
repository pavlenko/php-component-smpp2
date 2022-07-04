<?php

namespace PE\SMPP\PDU;

final class ReplaceSm extends PDU
{
    public function getCommandID(): int
    {
        return 0x00000007;
    }
}
