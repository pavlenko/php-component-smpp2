<?php

namespace PE\Component\SMPP\V3;

interface SerializerInterface
{
    public function encode(PDUInterface $pdu): string;

    public function decode(string $pdu): PDUInterface;
}
