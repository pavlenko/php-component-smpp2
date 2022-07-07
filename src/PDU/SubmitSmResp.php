<?php

namespace PE\SMPP\PDU;

use PE\SMPP\Util\Buffer;

class SubmitSmResp extends PDU
{
    private string $messageID;

    public function __construct($body = '')
    {
        parent::__construct($body);

        if (strlen($body) === 0) {
            return;
        }

        $this->setMessageID((new Buffer($body))->shiftString(64));
    }

    public function getCommandId(): int
    {
        return 0x80000004;
    }

    public function getMessageID(): string
    {
        return $this->messageID;
    }

    public function setMessageID(string $messageID): void
    {
        $this->messageID = $messageID;
    }

    public function __toString(): string
    {
        $buffer = new Buffer();
        $buffer->writeString($this->getMessageID());

        $this->setBody($buffer);
        return parent::__toString();
    }
}
