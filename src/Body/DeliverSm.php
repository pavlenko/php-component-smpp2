<?php

namespace PE\Component\SMPP\Body;

final class DeliverSm  extends SubmitSm
{
    public function getCommandID(): int
    {
        return 0x00000005;
    }
}
