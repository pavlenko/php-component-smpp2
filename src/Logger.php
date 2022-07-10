<?php

namespace PE\SMPP;

trait Logger
{
    public function log(string $level, string $message)
    {
        $pos = strrpos(self::class, "\\");
        $pre = false !== $pos ? substr(self::class, $pos + 1) : self::class;
        $this->logger->log($level, "[SMPP\\$pre] $message");
    }
}
