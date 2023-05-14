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
     * @throws InvalidPDUException If some param required but not passed
     * @throws UnknownPDUException If unknown command ID received
     */
    public function encode(PDU $pdu): string;
}
