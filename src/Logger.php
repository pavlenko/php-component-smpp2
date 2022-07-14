<?php

namespace PE\Component\SMPP;

trait Logger
{
    public function log(string $level, string $message, array $context = [])
    {
        $pos = strrpos(self::class, "\\");
        $pre = false !== $pos ? substr(self::class, $pos + 1) : self::class;

        $message = preg_replace_callback('/{([^{}]+)}/', fn($m) => $context[$m[1]] ?? '', $message);

        $this->logger->log($level, "[SMPP\\$pre] $message");
    }
}
