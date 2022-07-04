<?php

namespace PE\SMPP\PDU;

final class EnquireLink extends PDU
{
    public function getCommandID(): int
    {
        return 0x00000015;
    }
}
