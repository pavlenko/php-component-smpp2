<?php

namespace PE\Component\SMPP;

use PE\Component\Loop\Loop;
use PE\Component\Loop\LoopInterface;
use PE\Component\SMPP\Util\Serializer;
use PE\Component\SMPP\Util\SerializerInterface;
use PE\Component\Socket\ClientInterface as SocketClientInterface;
use PE\Component\Socket\FactoryInterface as SocketFactoryInterface;
use PE\Component\Socket\SelectInterface as SocketSelectInterface;
use PE\Component\Socket\ServerInterface as SocketServerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Factory4 implements FactoryInterface
{
    private SocketSelectInterface $socketSelect;
    private SocketFactoryInterface $socketFactory;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;

    public function __construct(
        SocketSelectInterface  $socketSelect,
        SocketFactoryInterface $socketFactory,
        SerializerInterface    $serializer = null,
        LoggerInterface        $logger = null
    ) {
        $this->socketSelect = $socketSelect;
        $this->socketFactory = $socketFactory;
        $this->serializer = $serializer ?: new Serializer();
        $this->logger = $logger ?: new NullLogger();
    }

    public function createSocketClient(string $addr, array $ctx = [], float $timeout = null): SocketClientInterface
    {
        return $this->socketFactory->createClient($addr, $ctx, $timeout);
    }

    public function createSocketServer(string $addr, array $ctx = []): SocketServerInterface
    {
        return $this->socketFactory->createServer($addr, $ctx);
    }

    public function createDispatcher(callable $dispatch): LoopInterface
    {
        return new Loop(1, function () use ($dispatch) {
            $this->socketSelect->dispatch();
            call_user_func($dispatch);
        });
    }

    public function createConnection(SocketClientInterface $client): Connection4
    {
        //TODO maybe split serializer to encoder/decoder
        return new Connection4($client, $this->serializer, $this->logger);
    }
}
