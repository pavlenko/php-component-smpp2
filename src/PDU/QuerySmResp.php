<?php

namespace PE\Component\SMPP\PDU;

final class QuerySmResp extends PDU
{
    public function getCommandID(): int
    {
        return 0x80000003;
    }
}
