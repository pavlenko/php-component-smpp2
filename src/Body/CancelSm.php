<?php

namespace PE\Component\SMPP\Body;

final class CancelSm extends PDU
{
    public function getCommandID(): int
    {
        return 0x00000008;
    }
}
