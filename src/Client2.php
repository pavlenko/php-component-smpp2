<?php

namespace PE\SMPP;

use PE\SMPP\PDU\BindTransmitter;
use PE\SMPP\PDU\PDU;
use PE\SMPP\Util\Buffer;
use PE\SMPP\Util\Stream;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Client2
{
    use Logger;

    private string $address;
    private string $systemID;
    private ?string $password;
    private LoggerInterface $logger;
    private ?Stream $stream = null;
    private ?Session $session = null;
    private array $sentPDUs = [];
    private array $waitPDUs = [];

    public function __construct(string $address, string $systemID, string $password = null, LoggerInterface $logger = null)
    {
        $this->address  = $address;
        $this->systemID = $systemID;
        $this->password = $password;
        $this->logger   = $logger ?: new NullLogger();
    }

    public function init()
    {
        $this->log(LogLevel::DEBUG, 'Connect to ' . $this->address);

        $this->stream = Stream::createClient($this->address, null, Session::TIMEOUT_CONNECT);
        $this->stream->setBlocking(false);

        $this->session = new Session($this->stream, $this->logger);

        $pdu = new BindTransmitter();
        $pdu->setSystemID($this->systemID);
        $pdu->setPassword($this->password);

        //$this->session->sendPDU($bind, PDU::BIND_TRANSMITTER_RESP, Session::TIMEOUT_RESPONSE);

        $this->log(LogLevel::DEBUG, sprintf('sendPDU(0x%08X)', $pdu->getCommandID()));
        $this->sentPDUs[] = new Packet($this->systemID, $pdu, PDU::BIND_TRANSMITTER_RESP, time() + Session::TIMEOUT_RESPONSE);
        $this->stream->sendData($pdu);
    }

    public function tick(): void
    {
        $this->log(LogLevel::INFO, 'tick');

        if (!$this->session) {
            return;
        }

        $r = [$this->stream];
        $w = [$this->stream];
        $n = [];
        Stream::select($r, $w, $n, 0);

        if (!empty($r) || !empty($w)) {
            $this->readPDU();
            //$this->handleReceive($this->session);
        }

        if ($this->session) {
            //$this->handleTimeout($this->session);
        }

        if ($this->session) {
            //$this->handlePending($this->session);
        }
    }

    private function readPDU()
    {
        //TODO try use react-loop or copy logic & extend base loop interface
        $head = $this->stream->recvData(16);

        if ($this->stream->isEOF() || '' === $head) {
            return null;
        }
var_dump($head);
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

        $this->log(LogLevel::DEBUG, sprintf('readPDU(0x%08X)', $pdu->getCommandID()));
        foreach ($this->sentPDUs as $key => $packet) {
            if ($packet->getExpectedResp() === $commandID && $packet->getPDU()->getSequenceNum() === $sequenceNum) {
                unset($this->sentPDUs[$key]);
            }
        }

        return $pdu;
    }
}
