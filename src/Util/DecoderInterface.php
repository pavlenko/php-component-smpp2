<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Exception\InvalidPDUException;
use PE\Component\SMPP\Exception\MalformedPDUException;
use PE\Component\SMPP\Exception\UnknownPDUException;

interface DecoderInterface
{
    /**
     * Decode PDU from binary string
     *
     * @param string $buffer
     * @return PDU
     * @throws MalformedPDUException If it cannot decode some part
     * @throws InvalidPDUException If some param required but not passed
     * @throws UnknownPDUException If unknown command ID received
     */
    public function decode(string $buffer): PDU;
}
