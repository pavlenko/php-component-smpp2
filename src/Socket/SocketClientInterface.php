<?php

namespace PE\Component\SMPP\Socket;

interface SocketClientInterface
{
    /**
     * Set handler for input event
     *
     * @param callable $handler
     */
    public function onInput(callable $handler): void;

    /**
     * Set handler for error event
     *
     * @param callable $handler
     */
    public function onError(callable $handler): void;

    /**
     * Set handler for close event
     *
     * @param callable $handler
     */
    public function onClose(callable $handler): void;

    /**
     * Write data to socket (buffered)
     *
     * @param string $data
     */
    public function write(string $data): void;

    /**
     * Close connection
     *
     * @param string|null $message Optional reason message
     */
    public function close(string $message = null): void;
}