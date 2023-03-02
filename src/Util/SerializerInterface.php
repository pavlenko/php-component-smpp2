<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\DTO\PDUInterface;

interface SerializerInterface
{
    /**
     * @param string $pdu
     *
     * @return PDUInterface
     */
    public function decode(string $pdu): PDUInterface;

    /**
     * @param PDUInterface $pdu
     *
     * @return string
     */
    public function encode(PDUInterface $pdu): string;
}
