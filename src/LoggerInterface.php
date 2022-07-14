<?php

namespace PE\Component\SMPP;

interface LoggerInterface
{
    /**
     * @param object $context
     * @param string $level
     * @param string $message
     */
    public function log(object $context, string $level, string $message): void;
}
