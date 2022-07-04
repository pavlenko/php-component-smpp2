<?php

namespace PE\SMPP\PDU;

use PE\SMPP\Decoder;

abstract class BindResp extends PDU
{
    private string $systemID;

    public function __construct($body = '')
    {
        parent::__construct($body);
        if (strlen($body) === null) {
            return;
        }

        $decoder = new Decoder($body);
        $this->setSystemID($decoder->readString(16));

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
