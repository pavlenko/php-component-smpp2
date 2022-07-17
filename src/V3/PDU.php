<?php

namespace PE\Component\SMPP\V3;

class PDU implements PDUInterface
{
    private int $id;
    private int $status;
    private int $seqNum;

    public function __construct(int $id, int $status, int $seqNum)
    {
        $this->id     = $id;
        $this->status = $status;
        $this->seqNum = $seqNum;
    }

    public function getID(): int
    {
        return $this->id;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getSeqNum(): int
    {
        return $this->seqNum;
    }
}
