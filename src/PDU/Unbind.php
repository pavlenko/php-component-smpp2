<?php

namespace PE\SMPP\PDU;

class Unbind extends PDU
{
    public function getCommandID(): int
    {
        return 0x00000006;
    }
}
