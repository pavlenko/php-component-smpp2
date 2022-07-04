<?php

namespace PE\SMPP\PDU;

final class QuerySm extends PDU
{
    public function getCommandID(): int
    {
        return 0x00000003;
    }
}
