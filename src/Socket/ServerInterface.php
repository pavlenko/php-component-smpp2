<?php

namespace PE\Component\SMPP\Socket;

interface ServerInterface
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
     * Close connection
     *
     * @param string|null $message Optional reason message
     */
    public function close(string $message = null): void;
}
