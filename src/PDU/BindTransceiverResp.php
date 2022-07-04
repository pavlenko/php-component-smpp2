<?php

namespace PE\SMPP\PDU;

class BindTransceiverResp extends BindResp
{
    public function getCommandID(): int
    {
        return 0x80000009;
    }
}
