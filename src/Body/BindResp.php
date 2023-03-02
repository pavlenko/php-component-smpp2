<?php

namespace PE\Component\SMPP\Body;

use PE\Component\SMPP\Util\Buffer;

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

        $buffer = new Buffer($body);
        $this->setSystemID($buffer->shiftString(16));

        if (!$buffer->isEOF()) {
            $tlv = $buffer->shiftTLV();
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
