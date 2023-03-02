<?php

namespace PE\Component\SMPP\Body;

final class Unbind extends PDU
{
    public function getCommandID(): int
    {
        return 0x00000006;
    }
}
