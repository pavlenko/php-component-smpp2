<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\Util\Stream;
use Psr\Log\LoggerInterface;

/* @deprecated */
interface FactoryOldInterface
{
    /**
     * Create connection for stream
     *
     * @param Stream $stream
     * @param LoggerInterface|null $logger
     *
     * @return ConnectionInterface
     */
    public function createStreamConnection(Stream $stream, LoggerInterface $logger = null): ConnectionInterface;

    /**
     * Create connection for client
     *
     * @param string $address
     * @param LoggerInterface|null $logger
     *
     * @return ConnectionInterface
     */
    public function createClientConnection(string $address, LoggerInterface $logger = null): ConnectionInterface;

    /**
     * Create connection for server
     *
     * @param string $address
     * @param LoggerInterface|null $logger
     *
     * @return ConnectionInterface
     */
    public function createServerConnection(string $address, LoggerInterface $logger = null): ConnectionInterface;
}
