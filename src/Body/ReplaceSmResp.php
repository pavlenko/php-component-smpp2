<?php

namespace PE\Component\SMPP\Body;

final class ReplaceSmResp extends PDU
{
    public function getCommandID(): int
    {
        return 0x80000007;
    }
}
