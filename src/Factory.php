<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\Util\Stream;
use PE\Component\SMPP\V3\Connection;
use PE\Component\SMPP\V3\ConnectionInterface;
use Psr\Log\LoggerInterface;

final class Factory implements FactoryInterface
{
    public function createStreamConnection(Stream $stream, LoggerInterface $logger = null): ConnectionInterface
    {
        return new Connection($stream, null, $logger);
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
