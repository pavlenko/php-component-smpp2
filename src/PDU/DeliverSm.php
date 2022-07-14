<?php

namespace PE\Component\SMPP\PDU;

final class DeliverSm  extends SubmitSm
{
    public function getCommandID(): int
    {
        return 0x00000005;
    }
}
