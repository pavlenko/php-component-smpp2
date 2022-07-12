<?php

namespace PE\SMPP;

use PE\SMPP\PDU\Address;
use PE\SMPP\PDU\BindTransmitter;
use PE\SMPP\PDU\DeliverSm;
use PE\SMPP\PDU\DeliverSmResp;
use PE\SMPP\PDU\EnquireLink;
use PE\SMPP\PDU\EnquireLinkResp;
use PE\SMPP\PDU\GenericNack;
use PE\SMPP\PDU\PDU;
use PE\SMPP\PDU\SubmitSm;
use PE\SMPP\Util\Stream;
use Psr\Log\LogLevel;

final class Client
{
    private string $address;
    private string $systemID;
    private ?string $password;
    private ?LoggerInterface $logger;

    private ?Session $session = null;

    /**
     * @var Packet[]
     */
    private array $waitPDUs = [];

    public function __construct(string $address, string $systemID, string $password = null, LoggerInterface $logger = null)
    {
        $this->address  = $address;
        $this->systemID = $systemID;
        $this->password = $password;
        $this->logger   = $logger;
    }

    public function init()
    {
        $this->logger && $this->logger->log(LogLevel::DEBUG, 'Connect to ' . $this->address);

        $stream = Stream::createClient($this->address, null, Session::TIMEOUT_CONNECT);
        $stream->setBlocking(false);

        $this->session = new Session($stream, $this->logger);

        $pdu = new BindTransmitter();
        $pdu->setSystemID($this->systemID);
        $pdu->setPassword($this->password ?: "\0");

        $this->session->sendPDU($pdu, PDU::BIND_TRANSMITTER_RESP, Session::TIMEOUT_RESPONSE);
    }

    public function send(Address $src, Address $dst, string $text): void
    {
        $pdu = new SubmitSm();
        $pdu->setSourceAddress($src);
        $pdu->setDestinationAddress($dst);
        $pdu->setShortMessage($text);

        $this->session->sendPDU($pdu, PDU::SUBMIT_SM_RESP, Session::TIMEOUT_RESPONSE);
    }

    public function tick(): bool
    {
        $this->logger && $this->logger->log(LogLevel::DEBUG, 'tick');
        if (!$this->session) {
            return false;
        }

        $r = [$this->session->getStream()];
        $n = [];
        Stream::select($r, $n, $n, 0.05);

        if (!empty($r)) {
            $this->handleReceive($this->session);
        }
        if ($this->session) {
            $this->handleTimeout($this->session);
        }
        if ($this->session) {
            $this->handlePending($this->session);
        }
        return true;
    }

    private function detachSession(Session $session, string $reason): void
    {
        if ($this->session === $session) {
            $this->logger && $this->logger->log(LogLevel::DEBUG, __FUNCTION__ . ', reason: ' . $reason);
            $this->session->close();
            $this->session = null;
        }
    }

    private function handleReceive(Session $session): void
    {
        $this->logger && $this->logger->log(LogLevel::DEBUG, __FUNCTION__);
        $pdu = $session->readPDU();
        if (null === $pdu) {
            $this->detachSession($session, 'NO RESPOND');
            return;
        }

        switch (true) {
            case ($pdu instanceof DeliverSm):
                $response = new DeliverSmResp();
                $response->setSequenceNum($pdu->getSequenceNum());
                break;
            case ($pdu instanceof EnquireLink):
                $response = new EnquireLinkResp();
                $response->setSequenceNum($pdu->getSequenceNum());
                break;
            default:
                $response = new GenericNack();
        }

        $session->sendPDU($response, null, Session::TIMEOUT_RESPONSE);
    }

    private function handleTimeout(Session $session): void
    {
        $this->logger && $this->logger->log(LogLevel::DEBUG, __FUNCTION__);
        $sent = $session->getSentPDUs();
        foreach ($sent as $packet) {
            if (time() > $packet->getExpectedTime()) {
                $this->detachSession($session, 'TIMED OUT');
                return;
            }
        }
    }

    private function handlePending(Session $session): void
    {
        $this->logger && $this->logger->log(LogLevel::DEBUG, __FUNCTION__);
        foreach ($this->waitPDUs as $key => $packet) {
            $session->sendPDU($packet->getPDU(), $packet->getExpectedResp(), $packet->getExpectedTime());
            unset($this->waitPDUs[$key]);
        }
    }

    public function stop(): void
    {
        $this->logger && $this->logger->log(LogLevel::DEBUG, 'stop');
        $this->detachSession($this->session, 'STOP SERVER');
    }
}
