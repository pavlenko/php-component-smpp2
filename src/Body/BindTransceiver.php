<?php

namespace PE\Component\SMPP\Body;

final class BindTransceiver extends Bind
{
    public function getCommandID(): int
    {
        return 0x00000009;
    }
}
