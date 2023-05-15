<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\PDU;

interface ValidatorInterface
{
    /**
     * @param PDU $pdu
     * @throws ValidatorException
     */
    public function validate(PDU $pdu): void;
}
