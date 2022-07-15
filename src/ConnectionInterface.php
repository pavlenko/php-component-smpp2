<?php

namespace PE\Component\SMPP;

interface ConnectionInterface
{
    public function open();//TODO <-- server/client/sender specific
    public function bind();//TODO <-- server/client/sender specific
    public function readPDU();
    public function sendPDU($pduData);
}
