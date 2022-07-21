<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Util\Stream;

final class Server implements ServerInterface
{
    private string $address;
    private FactoryInterface $factory;
    private ConnectionInterface $connection;
    private \SplObjectStorage $sessions;

    public function __construct(string $address, FactoryInterface $factory)
    {
        $this->address = $address;
        $this->factory = $factory;

        $this->sessions = new \SplObjectStorage();
    }

    public function bind(): void
    {
        $this->connection = $this->factory->createClientConnection($this->address);
    }

    public function tick(): void
    {
        $master = $this->connection->getStream();

        $r = array_merge([$master], iterator_to_array($this->sessions));
        $n = [];
        Stream::select($r, $n, $n, 0.05);//<-- here always need timeout, for prevent blocking process

        if (in_array($master, $r)) {
            unset($r[array_search($master, $r)]);
            //$this->acceptSession(new Session($this->master->accept(), $this->logger));
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
}
