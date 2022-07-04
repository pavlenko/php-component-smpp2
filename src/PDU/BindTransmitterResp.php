<?php

namespace PE\SMPP\PDU;

class BindTransmitterResp extends BindResp
{
    public function getCommandID(): int
    {
        return 0x80000002;
    }
}
