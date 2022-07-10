<?php

namespace PE\SMPP;

use PE\Component\Loop\LoopInterface;
use PE\SMPP\PDU\BindReceiver;
use PE\SMPP\PDU\BindReceiverResp;
use PE\SMPP\PDU\BindTransceiver;
use PE\SMPP\PDU\BindTransceiverResp;
use PE\SMPP\PDU\BindTransmitter;
use PE\SMPP\PDU\BindTransmitterResp;
use PE\SMPP\PDU\CancelSm;
use PE\SMPP\PDU\CancelSmResp;
use PE\SMPP\PDU\EnquireLink;
use PE\SMPP\PDU\EnquireLinkResp;
use PE\SMPP\PDU\GenericNack;
use PE\SMPP\PDU\PDU;
use PE\SMPP\PDU\QuerySm;
use PE\SMPP\PDU\QuerySmResp;
use PE\SMPP\PDU\ReplaceSm;
use PE\SMPP\PDU\ReplaceSmResp;
use PE\SMPP\PDU\SubmitSm;
use PE\SMPP\PDU\SubmitSmResp;
use PE\SMPP\PDU\Unbind;
use PE\SMPP\PDU\UnbindResp;
use PE\SMPP\Util\Stream;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class ServerV2
{
    use Events;
    use Logger;

    private string $address;
    private LoggerInterface $logger;
    private ?Stream $master = null;

    /**
     * @var \SplObjectStorage|Session[]
     */
    private \SplObjectStorage $sessions;

    /**
     * @var Packet[]
     */
    private array $waitPDUs = [];

    public function __construct(string $address, LoggerInterface $logger = null)
    {
        $this->address  = $address;
        $this->logger   = $logger;
        $this->sessions = new \SplObjectStorage();
    }

    public function run(LoopInterface $loop): void
    {
        $this->init();

        $loop->addPeriodicTimer(0.5, fn() => $this->tick());//TODO <-- config interval + maybe add ttl
        $loop->run();

        $this->stop();
    }

    private function init(): void
    {
        $this->log(LogLevel::INFO, 'init');
        $this->master = Stream::createServer($this->address);
        $this->master->setBlocking(false);
    }

    private function tick(): void
    {
        $this->log(LogLevel::INFO, 'tick');

        $r = array_merge([$this->master], iterator_to_array($this->sessions));
        $n = [];
        Stream::select($r, $n, $n, 0.05);//TODO <-- config

        if (in_array($this->master, $r)) {
            unset($r[array_search($this->master, $r)]);
            $this->acceptSession(new Session($this->master->accept(), $this->logger));
        }

        foreach ($r as $stream) {
            $this->processReceive($this->sessions[$stream]);
        }
        foreach ($this->sessions as $stream) {
            $this->processTimeout($this->sessions[$stream]);
        }
        foreach ($this->sessions as $stream) {
            $this->processEnquire($this->sessions[$stream]);
        }
        foreach ($this->sessions as $stream) {
            $this->processWaiting($this->sessions[$stream]);
        }
    }

    private function acceptSession(Session $session): void
    {
        $this->log(LogLevel::DEBUG, 'accept session ' . $session->getPeerName());
        $this->sessions->attach($session->getStream(), $session);
        //TODO here we can send outbind if has wait messages for client
    }

    private function detachSession(Session $session, string $reason): void
    {
        if ($this->sessions->contains($session->getStream())) {
            $this->log(LogLevel::DEBUG, 'detach session ' . $session->getPeerName() . ' reason: ' . $reason);
            $session->close();
            $this->sessions->detach($session->getStream());
        }
    }

    private function processReceive(Session $session): void
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
                $response = new GenericNack();
        }

        $response->setCommandStatus(CommandStatus::NO_ERROR);
        $response->setSequenceNum($pdu->getSequenceNum());

        $session->sendPDU($response, null, Session::TIMEOUT_RESPONSE);
    }

    private function processTimeout(Session $session): void
    {
        $sent = $session->getSentPDUs();
        foreach ($sent as $packet) {
            if (time() > $packet->getExpectedTime()) {
                $this->detachSession($session, 'TIMED OUT');
                return;
            }
        }
    }

    private function processEnquire(Session $session): void
    {
        if (time() - Session::TIMEOUT_ENQUIRE > $session->getEnquiredAt()) {
            $session->sendPDU(new EnquireLink(), PDU::ENQUIRE_LINK_RESP, Session::TIMEOUT_RESPONSE);
            $session->setEnquiredAt();
        }
    }

    private function processWaiting(Session $session): void
    {
        foreach ($this->waitPDUs as $key => $packet) {
            if ($session->getSystemID() !== $packet->getSystemID()) {
                continue;
            }
            $session->sendPDU($packet->getPDU(), $packet->getExpectedResp(), $packet->getExpectedTime());
            unset($this->waitPDUs[$key]);
        }
    }

    private function stop(): void
    {
        $this->log(LogLevel::INFO, 'stop');
        foreach ($this->sessions as $stream) {
            $this->detachSession($this->sessions[$stream], 'STOP SERVER');
        }

        if ($this->master) {
            $this->master->close();
            $this->master = null;
        }
    }
}
