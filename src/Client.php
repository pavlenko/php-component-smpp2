<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\PDU\Address;
use PE\Component\SMPP\PDU\BindResp;
use PE\Component\SMPP\PDU\BindTransmitter;
use PE\Component\SMPP\PDU\DeliverSm;
use PE\Component\SMPP\PDU\DeliverSmResp;
use PE\Component\SMPP\PDU\EnquireLink;
use PE\Component\SMPP\PDU\EnquireLinkResp;
use PE\Component\SMPP\PDU\GenericNack;
use PE\Component\SMPP\PDU\PDU;
use PE\Component\SMPP\PDU\SubmitSm;
use PE\Component\SMPP\Util\Stream;
use Psr\Log\LogLevel;

final class Client
{
    private string $address;
    private string $systemID;
    private ?string $password;
    private $logger;

    private $session = null;

    /**
     * @var Packet[]
     */
    private array $waitPDUs = [];

    public function __construct(string $address, string $systemID, string $password = null, $logger = null)
    {
        $this->address  = $address;
        $this->systemID = $systemID;
        $this->password = $password;
        $this->logger   = $logger;
    }

    public function init()
    {
        $this->logger && $this->logger->log($this, LogLevel::DEBUG, 'Connect to ' . $this->address);

        $stream = Stream::createClient($this->address, null, 30);
        $stream->setBlocking(false);

        $this->session = null;

        $pdu = new BindTransmitter();
        $pdu->setSystemID($this->systemID);
        $pdu->setPassword($this->password ?: "\0");

        $this->waitPDUs[] = new Packet($pdu, PDU::BIND_TRANSMITTER_RESP, 30);
    }

    public function send(Address $src, Address $dst, string $text): void
    {
        //TODO send logic:
        //TODO --> connect
        //TODO --> bind
        //TODO <-- bind_resp
        //TODO --> submit_sm
        //TODO <-- submit_sm_resp
        //TODO repeat for next messages???
        //TODO --> unbind
        //TODO <-- unbind_resp
        //TODO close

        $pdu = new SubmitSm();
        $pdu->setSourceAddress($src);
        $pdu->setDestinationAddress($dst);
        $pdu->setShortMessage($text);

        $this->session->sendPDU($pdu, PDU::SUBMIT_SM_RESP, 30);
    }

    public function tick(): bool
    {
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

    public function stop(): void
    {
        $this->logger->log($this, LogLevel::DEBUG, 'stop');
        if ($this->session) {
            $this->detachSession($this->session, 'STOP SERVER');
        }
    }
}
