<?php

namespace PE\Component\SMPP\Socket;

use PE\Component\Stream\Exception\RuntimeException;

interface SocketInterface
{
    /**
     * Get resource pointer if available
     *
     * @return resource|null
     * @internal DO NOT USE IN APP DIRECTLY
     */
    public function getResource();

    /**
     * Get full client/remote address of socket
     *
     * @param bool $remote
     * @return string|null
     */
    public function getAddress(bool $remote): ?string;

    /**
     * Enable/disable encryption on socket
     *
     * @param bool $enabled
     * @param int|null $method
     */
    public function setCrypto(bool $enabled, int $method = null): void;

    /**
     * Set read/write timeout
     *
     * @param int $seconds
     * @param int $micros
     */
    public function setTimeout(int $seconds, int $micros = 0): void;

    /**
     * Set blocking/non-blocking mode
     *
     * @param bool $enable
     */
    public function setBlocking(bool $enable): void;

    /**
     * Set the read buffer
     *
     * @param int $size The number of bytes to buffer. If <b>$size</b> is 0 then operations are unbuffered
     */
    public function setBufferRD(int $size): void;

    /**
     * Set the write buffer
     *
     * @param int $size The number of bytes to buffer. If <b>$size</b> is 0 then operations are unbuffered
     */
    public function setBufferWR(int $size): void;

    /**
     * Accept new client connection
     *
     * @param float|null $timeout
     * @return self
     */
    public function accept(float $timeout = null): self;

    /**
     * Check if stream closed by remote
     *
     * @return bool
     */
    public function isEOF(): bool;

    /**
     * Read data from stream
     *
     * @return string
     * @throws RuntimeException
     */
    public function recv(): string;

    /**
     * Send data to stream
     *
     * @param string $data
     * @return int
     */
    public function send(string $data): int;

    /**
     * Close stream
     */
    public function close(): void;
}
