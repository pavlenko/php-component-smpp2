<?php

namespace PE\Component\SMPP\PDU;

final class BindTransmitter extends Bind
{
    public function getCommandID(): int
    {
        return 0x00000002;
    }
}
