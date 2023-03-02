<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\V3\PDUInterface;

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
