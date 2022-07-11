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
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Client
{
    use Logger;

    private string $address;
    private string $systemID;
    private ?string $password;
    private LoggerInterface $logger;
    private ?Session $session = null;
    private array $pending = [];

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

        $stream = Stream::createClient($this->address, null, Session::TIMEOUT_CONNECT);
        $stream->setBlocking(false);

        $this->session = new Session($stream, $this->logger);

        $bind = new BindTransmitter();
        $bind->setSystemID($this->systemID);
        $bind->setPassword($this->password);

        $this->session->sendPDU($bind, PDU::BIND_TRANSMITTER_RESP, Session::TIMEOUT_RESPONSE);
    }

    public function send(Address $src, Address $dst, string $text): void
    {
        $pdu = new SubmitSm();
        $pdu->setSourceAddress($src);
        $pdu->setDestinationAddress($dst);
        $pdu->setShortMessage($text);

        $this->session->sendPDU($pdu, PDU::SUBMIT_SM_RESP, Session::TIMEOUT_RESPONSE);
    }

    public function tick(): void
    {
        $this->log(LogLevel::INFO, 'tick');

        if (!$this->session) {
            return;
        }

        $r = [$this->session->getStream()];
        $n = [];

        if (0 === Stream::select($r, $n, $n, 0.05)) {
            return;
        }

        if (!empty($r)) {
            $this->handleReceive($this->session);
        }

        if ($this->session) {
            $this->handleTimeout($this->session);
        }

        if ($this->session) {
            $this->handlePending($this->session);
        }
    }

    private function detachSession(Session $session, string $reason): void
    {
        if ($this->session === $session) {
            $this->log(LogLevel::DEBUG, 'detach session ' . $session->getPeerName() . ' reason: ' . $reason);
            $this->session->close();
            $this->session = null;
        }
    }

    private function handleReceive(Session $session): void
    {
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
        $this->logger->log(LogLevel::DEBUG, 'Process timeouts');
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
        foreach ($this->pending as $key => [$systemID, $pdu, $expectedResp, $timeout]) {
            if ($session->getSystemID() !== $systemID) {
                continue;
            }
            $session->sendPDU($pdu, $expectedResp, $timeout);
            unset($this->pending[$key]);
        }
        //TODO clear pending by valid till time
        //TODO store pending in external storage
    }

    public function stop(): void
    {
        $this->log(LogLevel::INFO, 'stop');
        $this->detachSession($this->session, 'STOP SERVER');
    }
}
