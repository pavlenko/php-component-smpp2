<?php

namespace PE\Component\SMPP\V3;

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
