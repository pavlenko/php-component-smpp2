<?php

namespace PE\Component\SMPP\PDU;

final class UnbindResp extends PDU
{
    public function getCommandID(): int
    {
        return 0x80000006;
    }
}
