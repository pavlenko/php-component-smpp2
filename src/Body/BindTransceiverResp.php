<?php

namespace PE\Component\SMPP\Body;

final class BindTransceiverResp extends BindResp
{
    public function getCommandID(): int
    {
        return 0x80000009;
    }
}
