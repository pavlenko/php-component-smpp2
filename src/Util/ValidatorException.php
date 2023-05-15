<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\Exception\ExceptionInterface;

final class ValidatorException extends \DomainException implements ExceptionInterface
{
    private int $status;

    public function __construct(string $message, int $status, \Throwable $previous = null)
    {
        $this->status = $status;
        parent::__construct($message, 0, $previous);
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
