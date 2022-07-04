<?php

namespace PE\SMPP\PDU;

class EnquireLinkResp extends PDU
{
    public function getCommandID(): int
    {
        return 0x80000015;
    }
}
