<?php

namespace PE\Component\SMPP\Socket;

interface ServerInterface
{
    /**
     * Get full address of this server listening on
     *
     * @return string|null
     */
    public function getAddress(): ?string;

    /**
     * Set handler for input event
     *
     * @param callable $handler
     */
    public function setInputHandler(callable $handler): void;

    /**
     * Set handler for error event
     *
     * @param callable $handler
     */
    public function setErrorHandler(callable $handler): void;

    /**
     * Set handler for close event
     *
     * @param callable $handler
     */
    public function setCloseHandler(callable $handler): void;

    /**
     * Close connection
     *
     * @param string|null $message Optional reason message
     */
    public function close(string $message = null): void;
}
