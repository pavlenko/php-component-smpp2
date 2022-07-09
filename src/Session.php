<?php

namespace PE\SMPP;

use PE\SMPP\PDU\PDU;
use PE\SMPP\Util\Buffer;
use PE\SMPP\Util\Stream;

final class Session
{
    public const MODE_TRANSMITTER = 1;
    public const MODE_RECEIVER    = 2;
    public const MODE_TRANSCEIVER = 3;

    public const TIMEOUT_CONNECT  = 10;
    public const TIMEOUT_ENQUIRE  = 5;
    public const TIMEOUT_RESPONSE = 10;

    private Stream $stream;

    /**
     * @var Packet[]
     */
    private array $sentPDUs = [];

    private ?string $systemID = null;
    private ?string $password = null;
    private int $enquiredAt;

    public function __construct(Stream $stream)
    {
        $this->stream = $stream;
        $this->setEnquiredAt();//TODO maybe datetime
    }

    /**
     * @return Packet[]
     */
    public function getSentPDUs(): array
    {
        return $this->sentPDUs;
    }

    public function getSystemID(): ?string
    {
        return $this->systemID;
    }

    public function setSystemID(string $systemID): void
    {
        $this->systemID = $systemID;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getEnquiredAt(): int
    {
        return $this->enquiredAt;
    }

    public function setEnquiredAt(): void
    {
        $this->enquiredAt = time();
    }

    public function readPDU(): ?PDU
    {
        $head = $this->stream->readData(16);

        if ('' === $head) {
            $this->stream->close();
            return null;
        }

        //TODO maybe move to factory class FROM
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
        $pdu->setSequenceNum($sequenceNum);
        //TODO maybe move to factory class TILL

        foreach ($this->sentPDUs as $key => $packet) {
            if ($packet->getExpectedResp() === $commandID && $packet->getPdu()->getSequenceNum() === $sequenceNum) {
                unset($this->sentPDUs[$key]);
            }
        }

        return $pdu;
    }

    public function sendPDU(PDU $pdu, int $expectedResp = null, int $timeout = null): bool
    {
        $this->sentPDUs[] = new Packet($pdu, $expectedResp, time() + $timeout);
        return (bool) $this->stream->sendData($pdu);
    }

    public function close(): self
    {
        $this->stream->close();
        return $this;
    }
}
