<?php

namespace PE\SMPP\PDU;

class SubmitSmResp extends PDU
{
    private string $messageID;

    public function __construct($body = '')
    {
        parent::__construct($body);

        if (strlen($body) === 0) {
            return;
        }

        return;//TODO
        $wrapper = new DataWrapper($body);
        $this->messageID = $wrapper->readNullTerminatedString(65);
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
        $builder = new Builder();
        $builder->addString($this->getMessageID());

        $this->setBody($builder);
        return parent::__toString();
    }
}
