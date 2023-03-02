<?php

namespace PE\Component\SMPP\Body;

final class QuerySm extends PDU
{
    public function getCommandID(): int
    {
        return 0x00000003;
    }
}
