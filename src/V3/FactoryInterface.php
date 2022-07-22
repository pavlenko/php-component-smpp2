<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Util\Stream;

interface FactoryInterface
{
    /**
     * Create connection for stream
     *
     * @param Stream $stream
     *
     * @return ConnectionInterface
     */
    public function createStreamConnection(Stream $stream): ConnectionInterface;

    /**
     * Create connection for client
     *
     * @param string $address
     *
     * @return ConnectionInterface
     */
    public function createClientConnection(string $address): ConnectionInterface;

    /**
     * Create connection for server
     *
     * @param string $address
     *
     * @return ConnectionInterface
     */
    public function createServerConnection(string $address): ConnectionInterface;
}
