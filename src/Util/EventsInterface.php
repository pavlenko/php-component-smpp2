<?php

namespace PE\Component\SMPP\Util;

/* @deprecated */
interface EventsInterface
{
    /**
     * Attach Event listener with optional priority
     *
     * @param string   $event
     * @param callable $listener
     * @param int      $priority
     */
    public function attach(string $event, callable $listener, int $priority = 0): void;

    /**
     * Detach event listener
     *
     * @param string   $event
     * @param callable $listener
     */
    public function detach(string $event, callable $listener): void;

    /**
     * Trigger all listener for specific event
     *
     * @param string $event
     * @param mixed  ...$arguments
     *
     * @return int Count of triggered listeners
     */
    public function trigger(string $event, ...$arguments): int;
}