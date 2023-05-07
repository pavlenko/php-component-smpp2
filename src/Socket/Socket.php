<?php

namespace PE\Component\SMPP\Socket;

use PE\Component\Stream\Exception\RuntimeException;

final class Socket implements SocketInterface
{
    /**
     * @var resource
     */
    private $resource;

    private bool $encrypted = false;

    public function __construct($resource)
    {
        if (!is_resource($resource) || get_resource_type($resource) !== 'stream') {
            throw new RuntimeException('First parameter must be a valid stream resource');
        }
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getAddress(bool $remote = false): ?string
    {
        if (!is_resource($this->resource)) {
            return null;
        }

        $address = @stream_socket_get_name($this->resource, $remote);

        // check if this is an IPv6 address which includes multiple colons but no square brackets
        $pos = strrpos($address, ':');
        if (false !== $pos && strpos($address, ':') < $pos && substr($address, 0, 1) !== '[') {
            $address = '[' . substr($address, 0, $pos) . ']:' . substr($address, $pos + 1);
        }

        return ($this->encrypted ? 'tls://' : 'tcp://') . $address;
    }

    public function setCrypto(bool $enabled, int $method = null): void
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

        $success = @stream_socket_enable_crypto($this->resource, $enabled, $method);
        restore_error_handler();

        if (false === $success) {
            throw new RuntimeException($error ?: 'Cannot set crypto method(s)');
        }
    }

    public function setTimeout(int $seconds, int $micros = 0): void
    {
        if (!stream_set_timeout($this->resource, $seconds, $micros)) {
            throw new RuntimeException('Cannot set read/write timeout');
        }
    }

    public function setBlocking(bool $enable): void
    {
        if (!stream_set_blocking($this->resource, $enable)) {
            throw new RuntimeException('Cannot set blocking mode');
        }
    }

    public function setBufferRD(int $size): void
    {
        if (0 !== stream_set_read_buffer($this->resource, $size)) {
            throw new RuntimeException('Cannot set read buffer');
        }
    }

    public function setBufferWR(int $size): void
    {
        if (0 !== stream_set_write_buffer($this->resource, $size)) {
            throw new RuntimeException('Cannot set write buffer');
        }
    }

    public function accept(float $timeout = null): SocketInterface
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

        $client = @stream_socket_accept($this->resource, $timeout);
        restore_error_handler();

        if (false === $client) {
            throw new RuntimeException($error ?: 'Unable to accept new connection');
        }

        $socket = new Socket($client);
        if ($this->encrypted) {
            $socket->setCrypto(true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        }

        return $socket;
    }

    public function isEOF(): bool
    {
        return feof($this->resource);
    }

    public function recv(): string
    {
        $error = null;
        set_error_handler(function ($code, $message) use (&$error) {
            $error = new RuntimeException('Unable to read from stream: ' . $message, $code);
        });

        $data = @stream_get_contents($this->resource);
        restore_error_handler();

        if (null !== $error) {
            $this->close();
            throw $error;
        }

        return $data;
    }

    public function send(string $data): int
    {
        $error = null;
        set_error_handler(function ($_, $message) use (&$error) {
            $error = new RuntimeException('Unable to write to stream: ' . $message);
        });

        $num = @fwrite($this->resource, $data);
        restore_error_handler();

        if (($num === 0 || $num === false) && $error !== null) {
            $this->close();
            throw $error;
        }

        return $num;
    }

    public function close(): void
    {
        if (is_resource($this->resource)) {
            @stream_socket_shutdown($this->resource, STREAM_SHUT_RDWR);
            @fclose($this->resource);
        }
    }
}
