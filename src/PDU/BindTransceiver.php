<?php

namespace PE\Component\SMPP\PDU;

final class BindTransceiver extends Bind
{
    public function getCommandID(): int
    {
        return 0x00000009;
    }
}
