<?php

namespace PE\SMPP;

use PE\Component\Loop\LoopInterface;
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

            $stream  = $this->master->accept();
            $session = new Session($stream, null);

            $this->sessions->attach($stream, $session);
            $this->log(LogLevel::INFO, 'Accepted conn from ' . $session->getPeerName());
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

    private function processReceive(Session $session): void
    {}

    private function processTimeout(Session $session): void
    {}

    private function processEnquire(Session $session): void
    {}

    private function processWaiting(Session $session): void
    {}


    private function stop(): void
    {
        $this->log(LogLevel::INFO, 'stop');
        foreach ($this->sessions as $stream) {
            $this->sessions[$stream]->close();
            $this->sessions->detach($stream);
        }

        if ($this->master) {
            $this->master->close();
            $this->master = null;
        }
    }
}
