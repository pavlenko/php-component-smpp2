<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\PDU;

interface DecoderInterface
{
    /**
     * Decode PDU from binary string
     *
     * @param string $buffer
     * @return PDU
     */
    public function decode(string $buffer): PDU;
}
