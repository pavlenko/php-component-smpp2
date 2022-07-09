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
use Psr\Log\NullLogger;

final class Client
{
    private string $systemID;
    private ?string $password;
    private LoggerInterface $logger;
    private ?Session $session = null;

    public function __construct(string $systemID, string $password = null, LoggerInterface $logger = null)
    {
        $this->systemID = $systemID;
        $this->password = $password;
        $this->logger   = $logger ?: new NullLogger();
    }

    public function init(string $address)
    {
        $stream = Stream::createClient($address, null, Session::TIMEOUT_CONNECT);
        $stream->setBlocking(false);

        $this->session = new Session($stream);

        $bind = new BindTransmitter();
        $bind->setSystemID($this->systemID);
        $bind->setPassword($this->password);

        $this->session->sendPDU($bind, PDU::BIND_TRANSMITTER_RESP, Session::TIMEOUT_RESPONSE);
    }

    public function tick(): void
    {
        $r = [$this->session->getStream()];
        $n = null;

        if (0 === Stream::select($r, $n, $n)) {
            $this->stop();
            return;
        }

        if (!empty($r)) {
            $this->handleReceive($this->session);
        }

        $this->handleTimeout($this->session);
    }

    private function handleReceive(Session $session): void//TODO make abstract for client & server
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

        $session->sendPDU($response);
    }

    private function handleTimeout(Session $session): void//TODO make abstract for client & server
    {
        $sent = $session->getSentPDUs();
        foreach ($sent as $packet) {
            if (time() > $packet->getExpectedTill()) {
                $session->close();
                return;
            }
        }
    }

    public function stop(): void
    {
        if ($this->stream) {
            $this->stream->close();
            $this->stream = null;
        }
    }
}
