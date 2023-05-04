<?php

namespace PE\Component\SMPP\Socket;

use PE\Component\Stream\Exception\InvalidArgumentException;
use PE\Component\Stream\Exception\RuntimeException;
use PE\Component\Stream\Stream;
use PE\Component\Stream\StreamInterface;

interface FactoryInterface
{
    /**
     * Accept incoming connection on master stream, must be used immediately after stream_select() call
     *
     * @param StreamInterface $master
     * @param float $timeout
     * @return SocketClientInterface
     * @throws RuntimeException
     */
    public function acceptClient(StreamInterface $master, float $timeout = 0): SocketClientInterface;

    /**
     * Create client socket
     *
     * @param string $address Address to the socket to connect to.
     * @param array $context Stream transport related context.
     * @param float|null $timeout Connection timeout.
     * @return SocketClientInterface
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function createClient(string $address, array $context = [], ?float $timeout = null): SocketClientInterface;

    /**
     * Create server socket
     *
     * @param string $address Address to the socket to listen to.
     * @param array $context Stream transport related context.
     * @return SocketServerInterface
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function createServer(string $address, array $context = []): SocketServerInterface;
}
