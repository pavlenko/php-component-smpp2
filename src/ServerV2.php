<?php

namespace PE\SMPP;

use PE\Component\Loop\LoopInterface;
use PE\SMPP\PDU\EnquireLink;
use PE\SMPP\PDU\PDU;
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
    {}

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
