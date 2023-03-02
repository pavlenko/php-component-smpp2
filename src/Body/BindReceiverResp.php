<?php

namespace PE\Component\SMPP\Body;

final class BindReceiverResp extends BindResp
{
    public function getCommandID(): int
    {
        return 0x80000001;
    }
}
