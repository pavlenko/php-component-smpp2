<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Util\Stream;

final class Factory implements FactoryInterface
{
    public function createStreamConnection(Stream $stream): ConnectionInterface
    {
        return new Connection($stream);
    }

    public function createClientConnection(string $address): ConnectionInterface
    {
        return $this->createStreamConnection(Stream::createClient($address));
    }

    public function createServerConnection(string $address): ConnectionInterface
    {
        return $this->createStreamConnection(Stream::createServer($address));
    }
}
