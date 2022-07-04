<?php

namespace PE\SMPP\PDU;

class QuerySmResp extends PDU
{
    public function getCommandID(): int
    {
        return 0x80000003;
    }
}
