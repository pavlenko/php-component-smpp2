<?php

namespace PE\Component\SMPP\Body;

final class DeliverSmResp extends SubmitSmResp
{
    public function getCommandID(): int
    {
        return 0x80000005;
    }
}
