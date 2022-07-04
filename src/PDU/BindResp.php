<?php

namespace PE\SMPP\PDU;

abstract class BindResp extends PDU
{
    private string $systemID;

    public function __construct($body = '')
    {
        parent::__construct($body);
        if (strlen($body) === null) {
            return;
        }

        return;//TODO parser/decoder
        $wrapper = new DataWrapper($body);
        $this->setSystemID(
            $wrapper->readNullTerminatedString(16)
        );
        /**
         * optional
         *
         * sc_interface_version TLV
         */
    }

    public function getSystemID(): string
    {
        return $this->systemID;
    }

    public function setSystemID(string $systemID): void
    {
        $this->systemID = $systemID;
    }
}
