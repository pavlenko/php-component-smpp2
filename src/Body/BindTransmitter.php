<?php

namespace PE\Component\SMPP\Body;

final class BindTransmitter extends Bind
{
    public function getCommandID(): int
    {
        return 0x00000002;
    }
}