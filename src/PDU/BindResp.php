<?php

namespace PE\SMPP\PDU;

use PE\SMPP\Decoder;

abstract class BindResp extends PDU
{
    private string $systemID;
    private int $interfaceVersion = 0;

    public function __construct($body = '')
    {
        parent::__construct($body);
        if (strlen($body) === null) {
            return;
        }

        $decoder = new Decoder($body);
        $this->setSystemID($decoder->readString(16));

        if (!$decoder->isEOF()) {
            $tlv = $decoder->readTLV();
            if (0x0210 === $tlv->getTag()) {// <-- sc_interface_version
                $this->setInterfaceVersion($tlv->getValue());//TODO decode value...
            }
        }
    }

    public function getSystemID(): string
    {
        return $this->systemID;
    }

    public function setSystemID(string $systemID): void
    {
        $this->systemID = $systemID;
    }

    public function getInterfaceVersion(): int
    {
        return $this->interfaceVersion;
    }

    public function setInterfaceVersion(int $interfaceVersion): void
    {
        $this->interfaceVersion = $interfaceVersion;
    }
}
