<?php

namespace PE\SMPP\PDU;

final class BindReceiverResp extends BindResp
{
    public function getCommandID(): int
    {
        return 0x80000001;
    }
}
