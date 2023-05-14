<?php

namespace PE\Component\SMPP;

use PE\Component\Loop\LoopInterface;
use PE\Component\Socket\ClientInterface as SocketClientInterface;
use PE\Component\Socket\ServerInterface as SocketServerInterface;

interface FactoryInterface
{
    public function createSocketClient(string $addr, array $ctx = [], float $timeout = null): SocketClientInterface;

    public function createSocketServer(string $addr, array $ctx = []): SocketServerInterface;

    public function createDispatcher(callable $dispatch): LoopInterface;

    public function createConnection(SocketClientInterface $client): ConnectionInterface;

    public function generateID(): string;
}
