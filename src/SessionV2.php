<?php

namespace PE\SMPP;

use PE\SMPP\PDU\PDU;
use PE\SMPP\Util\Stream;

class SessionV2
{
    public const EVENT_READ = 'session.read';
    public const EVENT_SEND = 'session.send';

    private Stream $stream;
    //TODO serializer to handle data coding
    private array $sent = [];//TODO packet: time, pdu, maybe some other options

    public function __construct(Stream $stream)
    {
        $this->stream = $stream;
    }

    public function getStream(): Stream
    {
        return $this->stream;
    }

    public function readPDU(): ?PDU//TODO automatically remove waited responses
    {}

    public function sendPDU(PDU $pdu, int $timeout = 0)//TODO response timeout? or expected result type?
    {}
}
