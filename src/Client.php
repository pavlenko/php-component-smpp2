<?php

namespace PE\SMPP;

use PE\SMPP\PDU\BindTransmitter;
use PE\SMPP\PDU\DeliverSm;
use PE\SMPP\PDU\DeliverSmResp;
use PE\SMPP\PDU\EnquireLink;
use PE\SMPP\PDU\EnquireLinkResp;
use PE\SMPP\PDU\GenericNack;
use PE\SMPP\PDU\PDU;
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

    public function tick(): void
    {
        $r = [$this->session->getStream()];
        $n = null;

        if (0 === Stream::select($r, $n, $n, 0.05)) {
            $this->stop();
            return;
        }

        if (!empty($r)) {
            $this->handleReceive($this->session);
        }

        $this->handleTimeout($this->session);
        $this->handlePending($this->session);
    }

    private function handleReceive(Session $session): void
    {
        $pdu = $session->readPDU();

        if (null === $pdu) {
            $session->close();
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
        $this->logger->log(LogLevel::DEBUG, 'Process timeouts from ' . $session->getPeerName());
        $sent = $session->getSentPDUs();
        foreach ($sent as $packet) {
            if (time() > $packet->getExpectedTill()) {
                $session->close();
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
        $this->log(LogLevel::DEBUG, 'Stopping client ...');
        $this->session->close();
        $this->log(LogLevel::DEBUG, 'Stopping client OK');
    }
}
