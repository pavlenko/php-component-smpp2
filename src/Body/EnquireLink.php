<?php

namespace PE\Component\SMPP\Body;

final class EnquireLink extends PDU
{
    public function getCommandID(): int
    {
        return 0x00000015;
    }
}
