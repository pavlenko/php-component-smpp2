<?php

namespace PE\SMPP;

trait Logger
{
    public function log(string $level, string $message)
    {
        $date = (new \DateTime())->format(\DateTime::RFC3339_EXTENDED);
        $pos = strrpos(self::class, "\\");
        $pre = false !== $pos ? substr(self::class, $pos + 1) : self::class;
        $this->logger->log($level, "[$date] [SMPP\\$pre] $message");
    }
}
