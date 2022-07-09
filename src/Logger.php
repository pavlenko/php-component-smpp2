<?php

namespace PE\SMPP;

trait Logger
{
    public function log(string $level, string $message)
    {
        $prefix = substr(self::class, strrpos(self::class, "\\"));
        $prefix = substr(self::class, strrpos(self::class, "\\", -strlen($prefix) - 1) + 1);
        $this->logger->log($level, "[$prefix] $message");
    }
}
