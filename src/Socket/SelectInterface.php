<?php

namespace PE\Component\SMPP\Socket;

interface SelectInterface
{
    /**
     * Attach resource to read listeners list
     *
     * @param resource $stream
     * @param callable $listener
     */
    public function attachStreamRD($stream, callable $listener): void;

    /**
     * Detach resource from read listeners list
     *
     * @param resource $stream
     */
    public function detachStreamRD($stream): void;

    /**
     * Attach resource to write listeners list
     *
     * @param resource $stream
     * @param callable $listener
     */
    public function attachStreamWR($stream, callable $listener): void;

    /**
     * Detach resource from write listeners list
     *
     * @param resource $stream
     */
    public function detachStreamWR($stream): void;

    /**
     * Dispatch streams, call to system select() method
     *
     * @param int|null $timeoutMs
     * @return int
     */
    public function dispatch(int $timeoutMs = null): int;
}