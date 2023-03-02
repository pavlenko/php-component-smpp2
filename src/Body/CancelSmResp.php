<?php

namespace PE\Component\SMPP\Body;

final class CancelSmResp extends PDU
{
    public function getCommandID(): int
    {
        return 0x00000008;
    }
}
