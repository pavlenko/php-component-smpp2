<?php

namespace PE\Component\SMPP\Socket;

use PE\Component\Stream\Exception\InvalidArgumentException;
use PE\Component\Stream\Exception\RuntimeException;

final class Factory implements FactoryInterface
{
    private SelectInterface $select;

    public function __construct(SelectInterface $select)
    {
        $this->select = $select;
    }

    public function createClient(string $address, array $context = [], ?float $timeout = null): ClientInterface
    {
        // Ensure scheme
        $address = false !== strpos($address, '://') ? $address : 'tcp://' . $address;

        // Extract parts
        ['scheme' => $scheme, 'host' => $host, 'port' => $port] = parse_url($address);

        // Validate parts
        if (!isset($scheme, $host, $port) || $scheme !== 'tcp' && $scheme !== 'tls') {
            throw new InvalidArgumentException('Invalid URI "' . $address . '" given (EINVAL)', SOCKET_EINVAL);
        }

        // Validate host
        if (false === @inet_pton(trim($host, '[]'))) {
            throw new InvalidArgumentException(
                'Given URI "' . $address . '" does not contain a valid host IP (EINVAL)',
                SOCKET_EINVAL
            );
        }

        $socket = @stream_socket_client(
            'tcp://' . $host . ':' . $port,
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

        $stream = new Socket($socket);
        if ('tls' === $scheme || !empty($context['ssl'])) {
            $stream->setCrypto(true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        }

        return new Client($stream, $this->select);
    }

    public function createServer(string $address, array $context = []): ServerInterface
    {
        // Ensure host
        $address = $address !== (string)(int)$address ? $address : '0.0.0.0:' . $address;

        // Ensure scheme
        $address = false !== strpos($address, '://') ? $address : 'tcp://' . $address;

        // Extract parts
        ['scheme' => $scheme, 'host' => $host, 'port' => $port] = parse_url($address);

        // Validate parts
        if (!isset($scheme, $host, $port) || $scheme !== 'tcp' && $scheme !== 'tls') {
            throw new InvalidArgumentException('Invalid URI "' . $address . '" given (EINVAL)', SOCKET_EINVAL);
        }

        // Validate host
        if (false === @inet_pton(trim($host, '[]'))) {
            throw new InvalidArgumentException(
                'Given URI "' . $address . '" does not contain a valid host IP (EINVAL)',
                SOCKET_EINVAL
            );
        }

        $socket = @stream_socket_server(
            'tcp://' . $host . ':' . $port,
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

        $stream = new Socket($socket);
        if ('tls' === $scheme || !empty($context['ssl'])) {
            $stream->setCrypto(true, STREAM_CRYPTO_METHOD_TLS_SERVER);
        }

        return new Server($stream, $this->select);
    }
}
