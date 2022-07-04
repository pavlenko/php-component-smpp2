<?php

namespace PE\SMPP\PDU;

class CancelSmResp extends PDU
{
    public function getCommandID(): int
    {
        return 0x00000008;
    }
}
