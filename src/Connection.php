<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\PDU\PDU;
use PE\Component\SMPP\Util\Buffer;

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

    public function sendPDU(PDU $pdu, Buffer $expectPDU = null): ?PDU
    {
        if (!is_resource($this->socket)) {
            throw new \RuntimeException('No connection has been established');
        }

        $result = fwrite($this->socket, $pdu);
        if (false === $result) {
            throw new \RuntimeException('Cannot send data');
        }

        if (null === $expectPDU) {
            return null;
        }

        return $this->readPDU();
    }

    public function readPDU(int $timeout = null): PDU
    {
        if (!is_resource($this->socket)) {
            throw new \RuntimeException('No connection has been established');
        }

        if ($timeout !== null) {
            stream_set_timeout($this->socket, $timeout);
        }

        $stream = new Buffer((string) fread($this->socket, 16));
        if ($stream->bytesLeft() < 16) {
            throw new \RuntimeException('Malformed PDU header');
        }

        $length        = $stream->shiftInt32();
        $commandID     = $stream->shiftInt32();
        $commandStatus = $stream->shiftInt32();
        $sequenceNum   = $stream->shiftInt32();

        if (!array_key_exists($commandID, PDU::CLASS_MAP)) {
            throw new \RuntimeException('Unknown PDU');
        }

        $body = (string) fread($this->socket, $length);
        if (strlen($body) < $length - 16) {
            throw new \RuntimeException('Malformed PDU body');
        }

        /* @var $pdu PDU */
        $cls = PDU::CLASS_MAP[$commandID];
        $pdu = new $cls($body);
        $pdu->setCommandStatus($commandStatus);
        $pdu->setSequenceNum($sequenceNum);
        return $pdu;
    }

    public function exit(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
            $this->socket = null;
        }
    }
}
