<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\PDU\BindReceiver;
use PE\Component\SMPP\PDU\BindReceiverResp;
use PE\Component\SMPP\PDU\BindTransceiver;
use PE\Component\SMPP\PDU\BindTransceiverResp;
use PE\Component\SMPP\PDU\BindTransmitter;
use PE\Component\SMPP\PDU\BindTransmitterResp;
use PE\Component\SMPP\PDU\CancelSm;
use PE\Component\SMPP\PDU\CancelSmResp;
use PE\Component\SMPP\PDU\EnquireLink;
use PE\Component\SMPP\PDU\EnquireLinkResp;
use PE\Component\SMPP\PDU\GenericNack;
use PE\Component\SMPP\PDU\PDU;
use PE\Component\SMPP\PDU\QuerySm;
use PE\Component\SMPP\PDU\QuerySmResp;
use PE\Component\SMPP\PDU\ReplaceSm;
use PE\Component\SMPP\PDU\ReplaceSmResp;
use PE\Component\SMPP\PDU\SubmitSm;
use PE\Component\SMPP\PDU\SubmitSmResp;
use PE\Component\SMPP\PDU\Unbind;
use PE\Component\SMPP\PDU\UnbindResp;
use PE\Component\SMPP\Util\Stream;
use Psr\Log\LogLevel;

final class Server
{
    private string $address;
    private LoggerInterface $logger;
    private ?Stream $master = null;

    /**
     * @var \SplObjectStorage|Session[]
     */
    private \SplObjectStorage $sessions;

    private array $pendings = [];

    public function __construct(string $address, LoggerInterface $logger = null)
    {
        $this->address  = $address;
        $this->logger   = $logger ?: new LoggerSTDOUT();
        $this->sessions = new \SplObjectStorage();
    }

    public function init(): void
    {
        $this->logger->log($this, LogLevel::DEBUG, 'Listen to ' . $this->address);
        $this->master = Stream::createServer($this->address);
        $this->master->setBlocking(false);

        if (0 === strpos($this->address, 'tls')) {
            $this->master->setCrypto(true, STREAM_CRYPTO_METHOD_TLS_SERVER);
        }
    }

    public function tick(): void
    {
        $r = array_merge([$this->master], iterator_to_array($this->sessions));
        $n = [];
        Stream::select($r, $n, $n, 0.05);//<-- here always need timeout, for prevent blocking process

        if (in_array($this->master, $r)) {
            unset($r[array_search($this->master, $r)]);
            $this->acceptSession(new Session($this->master->accept(), $this->logger));
        }

        foreach ($r as $stream) {
            $this->handleReceive($this->sessions[$stream]);
        }

        foreach ($this->sessions as $stream) {
            $this->handleTimeout($this->sessions[$stream]);
        }

        foreach ($this->sessions as $stream) {
            $this->handleEnquire($this->sessions[$stream]);
        }

        foreach ($this->sessions as $stream) {
            $this->handlePending($stream);
        }
    }

    private function acceptSession(Session $session): void
    {
        $this->logger->log($this, LogLevel::DEBUG, 'Accepted new connection from ' . $session->getPeerName());
        $this->sessions->attach($session->getStream(), $session);
    }

    private function detachSession(Session $session, string $reason): void
    {
        $this->logger->log($this, LogLevel::DEBUG, __FUNCTION__ . ', reason: ' . $reason);
        $session->close();
        $this->sessions->detach($session->getStream());
    }

    private function handleReceive(Session $session): void
    {
        $pdu = $session->readPDU();
        if (null === $pdu) {
            $this->detachSession($session, 'NO RESPOND');
            return;
        }

        switch (true) {
            case ($pdu instanceof BindReceiver):
                $session->setSystemID($pdu->getSystemID());
                $session->setPassword($pdu->getPassword());
                $response = new BindReceiverResp();
                break;
            case ($pdu instanceof BindTransmitter):
                $session->setSystemID($pdu->getSystemID());
                $session->setPassword($pdu->getPassword());
                $response = new BindTransmitterResp();
                break;
            case ($pdu instanceof BindTransceiver):
                $session->setSystemID($pdu->getSystemID());
                $session->setPassword($pdu->getPassword());
                $response = new BindTransceiverResp();
                break;
            case ($pdu instanceof Unbind):
                $response = new UnbindResp();//TODO <-- disconnect client
                break;
            case ($pdu instanceof SubmitSm):
                $response = new SubmitSmResp();
                $response->setMessageID(uniqid('', true));
                break;
            case ($pdu instanceof QuerySm):
                $response = new QuerySmResp();
                break;
            case ($pdu instanceof CancelSm):
                $response = new CancelSmResp();
                break;
            case ($pdu instanceof ReplaceSm)://TODO <-- add fields by spec
                $response = new ReplaceSmResp();
                break;
            case ($pdu instanceof EnquireLink):
                $response = new EnquireLinkResp();
                break;
            default:
                //$response = new GenericNack();
        }

        if (isset($response)) {
            $response->setCommandStatus(CommandStatus::NO_ERROR);
            $response->setSequenceNum($pdu->getSequenceNum());

            $session->sendPDU($response);
        }
    }

    private function handleTimeout(Session $session): void
    {
        foreach ($session->getSentPDUs() as $packet) {
            if (time() > $packet->getExpectedTime()) {
                $this->detachSession($session, 'TIMED OUT');
                return;
            }
        }
    }

    private function handleEnquire(Session $session): void
    {
        if (time() - Session::TIMEOUT_ENQUIRE > $session->getEnquiredAt()) {
            $session->sendPDU(new EnquireLink(), PDU::ENQUIRE_LINK_RESP, Session::TIMEOUT_RESPONSE);
            $session->setEnquiredAt();
        }
    }

    private function handlePending(Stream $stream): void
    {
        $toDelete = [];
        foreach ($this->pendings as $key => [$systemID, $pdu, $expectedResp, $timeout]) {
            if ($this->sessions[$stream]->getSystemID() !== $systemID) {
                continue;
            }
            $this->sessions[$stream]->sendPDU($pdu, $expectedResp, $timeout);
            $toDelete[] = $key;
        }
        foreach ($toDelete as $key) {
            unset($this->pendings[$key]);
        }
    }

    public function stop(): void
    {
        $this->logger->log($this, LogLevel::DEBUG, __FUNCTION__);
        foreach ($this->sessions as $stream) {
            $this->detachSession($this->sessions[$stream], 'STOP SERVER');
        }

        if ($this->master) {
            $this->master->close();
            $this->master = null;
        }
    }
}
