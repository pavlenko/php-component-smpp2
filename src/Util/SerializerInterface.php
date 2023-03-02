<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\PDU;

interface SerializerInterface
{
    /**
     * @param string $pdu
     *
     * @return PDU
     */
    public function decode(string $pdu): PDU;

    /**
     * @param PDU $pdu
     *
     * @return string
     */
    public function encode(PDU $pdu): string;
}
