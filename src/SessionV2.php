<?php

namespace PE\SMPP;

use PE\SMPP\PDU\PDU;
use PE\SMPP\Util\Buffer;
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

    public function readPDU(): ?PDU
    {
        //TODO automatically remove waited responses
        $head = $this->stream->readData(16);

        if ('' === $head) {
            $this->stream->close();
            return null;
        }

        $buffer = new Buffer($head);
        if ($buffer->bytesLeft() < 16) {
            throw new \RuntimeException('Malformed PDU header');
        }

        $length        = $buffer->shiftInt32();
        $commandID     = $buffer->shiftInt32();
        $commandStatus = $buffer->shiftInt32();
        $sequenceNum   = $buffer->shiftInt32();

        $body = $this->stream->readData($length);
        if (strlen($body) < $length - 16) {
            throw new \RuntimeException('Malformed PDU body');
        }

        /* @var $pdu PDU */
        $cls = PDU::CLASS_MAP[$commandID];
        $pdu = new $cls($body);
        $pdu->setCommandStatus($commandStatus);
        $pdu->setSequenceNumber($sequenceNum);

        return $pdu;
    }

    public function sendPDU(PDU $pdu, int $timeout = 0)//TODO response timeout? or expected result type?
    {}

    public function close(): self
    {
        $this->stream->close();
        return $this;
    }
}
