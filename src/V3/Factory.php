<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Util\Stream;

final class Factory implements FactoryInterface
{
    public function createClientConnection(string $address): ConnectionInterface
    {
        return new Connection(Stream::createClient($address));
    }

    public function createServerConnection(string $address): ConnectionInterface
    {
        return new Connection(Stream::createServer($address));
    }
}
