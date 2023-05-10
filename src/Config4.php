<?php

namespace PE\Component\SMPP;

final class Config4
{
    public const DEFAULT_RESPONSE_TIMEOUT = 5;
    public const DEFAULT_INACTIVE_TIMEOUT = 30;

    private int $responseTimeout;
    private int $inactiveTimeout;

    //TODO maybe to session
    public function __construct(
        int $responseTimeout = self::DEFAULT_RESPONSE_TIMEOUT,
        int $inactiveTimeout = self::DEFAULT_INACTIVE_TIMEOUT
    ) {
        $this->responseTimeout = $responseTimeout;
        $this->inactiveTimeout = $inactiveTimeout;
    }

    public function getResponseTimeout(): int
    {
        return $this->responseTimeout;
    }

    public function getInactiveTimeout(): int
    {
        return $this->inactiveTimeout;
    }
}
