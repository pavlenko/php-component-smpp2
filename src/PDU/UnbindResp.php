<?php

namespace PE\SMPP\PDU;

class UnbindResp extends PDU
{
    public function getCommandID(): int
    {
        return 0x80000006;
    }
}
