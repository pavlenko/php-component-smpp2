<?php

namespace PE\Component\SMPP\PDU;

final class BindTransmitterResp extends BindResp
{
    public function getCommandID(): int
    {
        return 0x80000002;
    }
}
