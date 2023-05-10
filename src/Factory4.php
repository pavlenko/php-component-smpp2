<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\Util\Serializer;
use PE\Component\SMPP\Util\SerializerInterface;
use PE\Component\Socket\FactoryInterface as SocketFactoryInterface;
use PE\Component\Socket\SelectInterface as SocketSelectInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Factory4 implements FactoryInterface
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

    public function getSocketSelect(): SocketSelectInterface
    {
        return $this->socketSelect;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function createConnection(string $address, array $context = [], float $timeout = null): Connection4
    {
        //TODO maybe split serializer to encoder/decoder
        return new Connection4(
            $this->socketFactory->createClient($address, $context, $timeout),
            $this->serializer,
            $this->logger
        );
    }
}
