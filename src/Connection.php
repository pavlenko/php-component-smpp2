<?php

namespace PE\SMPP;

class Connection
{
    private string $host;
    private int $port;

    /**
     * @var resource
     */
    private $socket;

    /**
     * @param string $host
     * @param int    $port
     */
    public function __construct(string $host = 'localhost', int $port = 2775)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function getHost(): string
    {
        return $this->host ?: 'localhost';
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function getPort(): int
    {
        return $this->port ?: 2775;
    }

    public function setPort(int $port): void
    {
        $this->port = $port;
    }

    public function isAlive(): bool
    {
        return null !== $this->socket && !feof($this->socket);
    }

    public function connect(): void
    {
        if (is_resource($this->socket)) {
            return;
        }

        $errorNum = 0;
        $errorStr = '';

        set_error_handler(static function ($error, $message = '') {
            throw new \RuntimeException(sprintf('Could not open socket: %s', $message), $error);
        }, E_WARNING);

        $this->socket = stream_socket_client(
            'tcp://' . $this->getHost() . ':' . $this->getPort(),
            $errorNum,
            $errorStr,
            5
        );

        restore_error_handler();

        if ($this->socket === false) {
            if ($errorNum === 0) {
                $errorStr = 'Could not open socket';
            }

            throw new \RuntimeException($errorStr);
        }

        if (false === stream_set_timeout($this->socket, 5)) {
            throw new \RuntimeException('Could not set stream timeout');
        }
    }

    public function send(string $data): int
    {
        if (!is_resource($this->socket)) {
            throw new \RuntimeException('No connection has been established');
        }

        if (false === ($result = fwrite($this->socket, $data . "\r\n"))) {
            throw new \RuntimeException('Cannot send data');
        }

        return $result;
    }

    public function read(int $timeout = null)
    {
        if (!is_resource($this->socket)) {
            throw new \RuntimeException('No connection has been established');
        }

        if ($timeout !== null) {
            stream_set_timeout($this->socket, $timeout);
        }

        //TODO

        $response = [];

        do {
            $line = fgets($this->socket);
            $info = stream_get_meta_data($this->socket);

            if ($info['timed_out']) {
                throw new \RuntimeException('Connection timed out');
            }

            if (false === $line) {
                throw new \RuntimeException('Cannot read data');
            }

            [$code, $message] = preg_split('/([\s-]+)/', $line, 2);

            $response[] = trim($message);
        } while (' ' !== $line[3]);

        return new Response($code, array_shift($response), $response);
    }

    public function exit(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
            $this->socket = null;
        }
    }
}
