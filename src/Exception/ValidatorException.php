<?php

namespace PE\Component\SMPP\Exception;

final class ValidatorException extends \DomainException implements ExceptionInterface
{
    public function __construct(string $message, int $status, \Throwable $previous = null)
    {
        parent::__construct($message, $status, $previous);
    }
}
