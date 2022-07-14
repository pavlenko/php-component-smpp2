<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\PDU\PDU;
use PE\Component\SMPP\Util\Buffer;
use PE\Component\SMPP\Util\Stream;
use Psr\Log\LogLevel;

//TODO sequence num processing
//TODO session status, opened/closed/etc
//TODO configurable timeouts
//TODO maybe extract read/send logic outside
final class Session
{
    public const MODE_TRANSMITTER = 1;
    public const MODE_RECEIVER    = 2;
    public const MODE_TRANSCEIVER = 3;

    public const TIMEOUT_CONNECT  = 10;
    public const TIMEOUT_ENQUIRE  = 5;
    public const TIMEOUT_RESPONSE = 10;

    private Stream $stream;
    private LoggerInterface $logger;

    /**
     * @var Packet[]
     */
    private array $sentPDUs = [];

    private ?string $systemID = null;
    private ?string $password = null;
    private int $enquiredAt;

    public function __construct(Stream $stream, LoggerInterface $logger = null)
    {
        $this->stream = $stream;
        $this->logger = $logger ?: new LoggerSTDOUT();
        $this->setEnquiredAt();
    }

    public function getStream(): Stream
    {
        return $this->stream;
    }

    public function getPeerName(): string
    {
        return $this->stream->getPeerName();
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
        if ($this->stream->isEOF()) {
            $this->logger->log($this, LogLevel::WARNING, __FUNCTION__ . ':EOF');
            return null;
        }

        $head = $this->stream->readData(16);
        if ('' === $head) {
            $this->logger->log($this, LogLevel::WARNING, __FUNCTION__ . ':EMPTY');
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

        $body = '';
        if ($length - 16 > 0) {
            $body = $this->stream->readData($length - 16);
            if (strlen($body) < $length - 16) {
                throw new \RuntimeException('Malformed PDU body');
            }
        }

        /* @var $pdu PDU */
        $cls = PDU::CLASS_MAP[$commandID];
        $pdu = new $cls($body);
        $pdu->setCommandStatus($commandStatus);
        $pdu->setSequenceNum($sequenceNum);

        $this->logger->log($this, LogLevel::DEBUG, sprintf('readPDU(0x%08X)', $pdu->getCommandID()));

        foreach ($this->sentPDUs as $key => $packet) {
            if ($packet->getExpectedResp() === $commandID && $packet->getPDU()->getSequenceNum() === $sequenceNum) {
                unset($this->sentPDUs[$key]);
            }
        }

        return $pdu;
    }

    //TODO maybe if no timeout - try read response immediately
    public function sendPDU(PDU $pdu, int $expectedResp = null, int $timeout = null): bool
    {
        $this->logger->log($this, LogLevel::DEBUG, strtr('sendPDU({pdu}, {res}, {tim})', [
            '{pdu}' => sprintf('0x%08X', $pdu->getCommandID()),
            '{res}' => null === $expectedResp ? 'NULL' : sprintf('0x%08X', $expectedResp),
            '{tim}' => null === $timeout ? 'NULL' : $timeout,
        ]));
        if (null !== $expectedResp || null !== $timeout) {
            $this->sentPDUs[] = new Packet($pdu, $expectedResp, time() + $timeout);
        }
        return (bool) $this->stream->sendData($pdu);
    }

    public function close(): self
    {
        $this->stream->close();
        return $this;
    }
}
