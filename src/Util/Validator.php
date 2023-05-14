<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\PDU;

final class Validator
{
    public function validate(PDU $pdu): void
    {
        //TODO validate and throw ValidatorException if some validation error occurred
    }
}
