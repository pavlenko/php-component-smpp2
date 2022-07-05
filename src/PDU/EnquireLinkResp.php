<?php

namespace PE\SMPP\PDU;

final class EnquireLinkResp extends PDU
{
    public function getCommandID(): int
    {
        return 0x80000015;
    }
}
