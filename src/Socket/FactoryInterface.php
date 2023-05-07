<?php

namespace PE\Component\SMPP\Socket;

use PE\Component\Stream\Exception\InvalidArgumentException;
use PE\Component\Stream\Exception\RuntimeException;

interface FactoryInterface
{
    /**
     * Create client socket
     *
     * @param string $address Address to the socket to connect to.
     * @param array $context Stream transport related context.
     * @param float|null $timeout Connection timeout.
     * @return ClientInterface
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function createClient(string $address, array $context = [], ?float $timeout = null): ClientInterface;

    /**
     * Create server socket
     *
     * @param string $address Address to the socket to listen to.
     * @param array $context Stream transport related context.
     * @return ServerInterface
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function createServer(string $address, array $context = []): ServerInterface;
}
