<?php

namespace PE\Component\SMPP\V3;

class PDU implements PDUInterface
{
    private int $status;

    public function __construct(int $status = 0)
    {
        $this->status = $status;
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
