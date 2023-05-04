<?php

namespace PE\Component\SMPP\Socket;

use PE\Component\Stream\Exception\InvalidArgumentException;
use PE\Component\Stream\Exception\RuntimeException;
use PE\Component\Stream\SelectInterface;
use PE\Component\Stream\Stream;
use PE\Component\Stream\StreamInterface;

final class Factory implements FactoryInterface
{
    private SelectInterface $select;

    public function __construct(SelectInterface $select)
    {
        $this->select = $select;
    }

    public function acceptClient(StreamInterface $master, float $timeout = 0): SocketClientInterface
    {
        $error = new RuntimeException('Unable to accept new connection');
        set_error_handler(function ($_, $message) use (&$error) {
            // @codeCoverageIgnoreStart
            $error = self::toException($error->getMessage(), \preg_replace('#.*: #', '', $message));
            // @codeCoverageIgnoreEnd
        });

        $socket = stream_socket_accept($master->getResource(), $timeout);
        restore_error_handler();

        if (false === $socket) {
            throw $error;
        }

        return new SocketClient(new Stream($socket), $this->select);//TODO try unwrap resource
    }

    public function createClient(string $address, array $context = [], ?float $timeout = null): SocketClientInterface
    {
        $address = self::toAddress($address, $scheme);

        $socket = @stream_socket_client(
            'tcp://' . $address,
            $errno,
            $error,
            $timeout,
            STREAM_CLIENT_CONNECT|STREAM_CLIENT_ASYNC_CONNECT,
            stream_context_create($context)
        );

        if (false === $socket) {
            throw new RuntimeException(
                'Connection to "' . $address . '" failed: ' . preg_replace('#.*: #', '', $error),
                $errno
            );
        }

        $stream = new Stream($socket);
        if ('tls' === $scheme || !empty($context['ssl'])) {
            $this->setCrypto($stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        }

        return new SocketClient($stream, $this->select);
    }

    public function createServer(string $address, array $context = []): SocketServerInterface
    {
        $address = self::toAddress($address, $scheme);

        $socket = @stream_socket_server(
            'tcp://' . $address,
            $errno,
            $error,
            STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
            stream_context_create($context)
        );

        if (false === $socket) {
            throw new RuntimeException(
                'Failed to listen on "' . $address . '": ' . preg_replace('#.*: #', '', $error),
                $errno
            );
        }

        $stream = new Stream($socket);
        if ('tls' === $scheme || !empty($context['ssl'])) {
            $this->setCrypto($stream, true, STREAM_CRYPTO_METHOD_TLS_SERVER);
        }

        return new SocketServer($stream, $this->select, $this);
    }
}
