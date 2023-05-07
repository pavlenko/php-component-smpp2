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

    public function acceptClient(StreamInterface $master, float $timeout = 0): ClientInterface
    {
        $error = null;
        set_error_handler(function ($_, $message) use (&$error) {
            // @codeCoverageIgnoreStart
            foreach (get_defined_constants() as $name => $value) {
                if (0 === strpos($name, 'SOCKET_E') && socket_strerror($value) === $message) {
                    $error = \preg_replace('#.*: #', '', $message) . ' (' . \substr($name, 7) . ')';
                }
            }
            // @codeCoverageIgnoreEnd
        });

        $socket = @stream_socket_accept($master->getResource(), $timeout);
        restore_error_handler();

        if (false === $socket) {
            throw new RuntimeException($error ?: 'Unable to accept new connection');
        }

        return new Client(new Stream($socket), $this->select);//TODO try unwrap resource
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

        $stream = new Stream($socket);
        if ('tls' === $scheme || !empty($context['ssl'])) {
            $this->setCrypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
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

        $stream = new Stream($socket);
        if ('tls' === $scheme || !empty($context['ssl'])) {
            $this->setCrypto($socket, true, STREAM_CRYPTO_METHOD_TLS_SERVER);
        }

        return new Server($stream, $this->select, $this);
    }

    /* @deprecated */
    public function setCrypto($stream, bool $enabled, int $method = null): void
    {
        $error = null;
        set_error_handler(function ($_, $message) use (&$error) {
            // @codeCoverageIgnoreStart
            $error = str_replace(["\r", "\n"], ' ', $message);

            // remove useless function name from error message
            if (false !== ($pos = strpos($error, "): "))) {
                $error = substr($error, $pos + 3);
            }
            // @codeCoverageIgnoreEnd
        });

        $success = @stream_socket_enable_crypto($stream, $enabled, $method);
        restore_error_handler();

        if (false === $success) {
            throw new RuntimeException($error ?: 'Cannot set crypto method(s)');
        }
    }
}
