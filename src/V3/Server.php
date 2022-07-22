<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Util\Stream;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Server implements ServerInterface
{
    private string $address;
    private FactoryInterface $factory;
    private LoggerInterface $logger;
    private ConnectionInterface $connection;
    private \SplObjectStorage $sessions;

    public function __construct(string $address, FactoryInterface $factory, LoggerInterface $logger = null)
    {
        $this->address = $address;
        $this->factory = $factory;
        $this->logger  = $logger ?: new NullLogger();

        $this->sessions = new \SplObjectStorage();
    }

    public function bind(): void
    {
        $this->connection = $this->factory->createServerConnection($this->address);
    }

    public function tick(): void
    {
        $master = $this->connection->getStream();

        $r = array_merge([$master], iterator_to_array($this->sessions));
        $n = [];
        Stream::select($r, $n, $n, 0.01);//<-- here always need timeout, for prevent blocking process

        if (in_array($master, $r)) {
            unset($r[array_search($master, $r)]);
            $this->attachConnection($master->accept());
        }

        foreach ($r as $stream) {
            //$this->handleReceive($this->sessions[$stream]);
        }

        foreach ($this->sessions as $stream) {
            //$this->handleTimeout($this->sessions[$stream]);
        }

        foreach ($this->sessions as $stream) {
            //$this->handleEnquire($this->sessions[$stream]);
        }

        foreach ($this->sessions as $stream) {
            //$this->handlePending($stream);
        }
    }

    private function attachConnection(Stream $stream): void
    {
        $this->logger->log(LogLevel::INFO, 'Accept new connection');
        $this->sessions->attach($stream, $this->factory->createStreamConnection($stream));
        //TODO wait for BIND
    }
}
