<?php

namespace PE\Component\SMPP\PDU;

final class DeliverSmResp extends SubmitSmResp
{
    public function getCommandID(): int
    {
        return 0x80000005;
    }
}
