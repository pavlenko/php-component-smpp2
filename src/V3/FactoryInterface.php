<?php

namespace PE\Component\SMPP\V3;

interface FactoryInterface
{
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
