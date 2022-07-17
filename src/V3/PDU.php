<?php

namespace PE\Component\SMPP\V3;

class PDU implements PDUInterface
{
    private int $id;
    private int $status;

    public function __construct(int $id, int $status = 0)
    {
        $this->id     = $id;
        $this->status = $status;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
