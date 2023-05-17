<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Exception\InvalidPDUException;
use PE\Component\SMPP\Exception\UnknownPDUException;

interface EncoderInterface
{
    /**
     * Encode PDU to binary string
     *
     * @param PDU $pdu
     * @return string
     */
    public function encode(PDU $pdu): string;
}
