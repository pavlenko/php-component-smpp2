<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\Util\Serializer;
use PE\Component\SMPP\Util\SerializerInterface;
use PE\Component\SMPP\Util\Stream;
use Psr\Log\LoggerInterface;

/* @deprecated */
final class FactoryOld implements FactoryOldInterface
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer = null)
    {
        $this->serializer = $serializer ?: new Serializer();
    }

    public function createStreamConnection(Stream $stream, LoggerInterface $logger = null): ConnectionInterface
    {
        return new Connection($stream, $this->serializer, $logger);
    }

    public function createClientConnection(string $address, LoggerInterface $logger = null): ConnectionInterface
    {
        return $this->createStreamConnection(Stream::createClient($address), $logger);
    }

    public function createServerConnection(string $address, LoggerInterface $logger = null): ConnectionInterface
    {
        return $this->createStreamConnection(Stream::createServer($address), $logger);
    }
}
