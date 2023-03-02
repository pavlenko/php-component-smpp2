<?php

namespace PE\Component\SMPP\Body;

final class BindReceiver extends Bind
{
    public function getCommandID(): int
    {
        return 0x00000001;
    }
}
