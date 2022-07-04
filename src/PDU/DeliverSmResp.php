<?php

namespace PE\SMPP\PDU;

class DeliverSmResp extends SubmitSmResp
{
    public function getCommandID(): int
    {
        return 0x80000005;
    }
}
