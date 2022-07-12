<?php

namespace PE\SMPP;

interface LoggerInterface
{
    /**
     * @param string $level
     * @param string $message
     */
    public function log(string $level, string $message): void;
}
