<?php

namespace PE\Component\SMPP\Body;

final class EnquireLinkResp extends PDU
{
    public function getCommandID(): int
    {
        return 0x80000015;
    }
}
