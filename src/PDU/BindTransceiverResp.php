<?php

namespace PE\Component\SMPP\PDU;

final class BindTransceiverResp extends BindResp
{
    public function getCommandID(): int
    {
        return 0x80000009;
    }
}
