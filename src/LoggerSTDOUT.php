<?php

namespace PE\SMPP;

use Psr\Log\LogLevel;

class LoggerSTDOUT implements LoggerInterface
{
    public const VERBOSITY_QUIET        = 1;
    public const VERBOSITY_NORMAL       = 2;
    public const VERBOSITY_VERBOSE      = 4;
    public const VERBOSITY_VERY_VERBOSE = 8;
    public const VERBOSITY_DEBUG        = 16;

    private const VERBOSITY_MAP = [
        LogLevel::EMERGENCY => self::VERBOSITY_NORMAL,
        LogLevel::ALERT     => self::VERBOSITY_NORMAL,
        LogLevel::CRITICAL  => self::VERBOSITY_NORMAL,
        LogLevel::ERROR     => self::VERBOSITY_NORMAL,
        LogLevel::WARNING   => self::VERBOSITY_NORMAL,
        LogLevel::NOTICE    => self::VERBOSITY_VERBOSE,
        LogLevel::INFO      => self::VERBOSITY_VERY_VERBOSE,
        LogLevel::DEBUG     => self::VERBOSITY_DEBUG,
    ];

    private const OUTPUT_MAP = [
        LogLevel::EMERGENCY => STDERR,
        LogLevel::ALERT     => STDERR,
        LogLevel::CRITICAL  => STDERR,
        LogLevel::ERROR     => STDERR,
        LogLevel::WARNING   => STDOUT,
        LogLevel::NOTICE    => STDOUT,
        LogLevel::INFO      => STDOUT,
        LogLevel::DEBUG     => STDOUT,
    ];

    private int $verbosity;

    public function __construct(int $verbosity = self::VERBOSITY_NORMAL)
    {
        $this->verbosity = $verbosity;
    }

    public function log(string $level, string $message): void
    {
        if (!array_key_exists($level, self::OUTPUT_MAP)) {
            $level = LogLevel::NOTICE;
        }

        if (self::VERBOSITY_MAP[$level] > $this->verbosity) {
            return;
        }

        fwrite(self::OUTPUT_MAP[$level], $message);
    }
}
