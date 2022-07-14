<?php

namespace PE\Component\SMPP\PDU;

final class CancelSm extends PDU
{
    public function getCommandID(): int
    {
        return 0x00000008;
    }
}
