<?php

namespace PE\SMPP\PDU;

final class ReplaceSmResp extends PDU
{
    public function getCommandID(): int
    {
        return 0x80000007;
    }
}
