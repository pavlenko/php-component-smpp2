<?php

namespace PE\Component\SMPP\Util;

use PE\Component\SMPP\Exception\ExceptionInterface;

final class ValidatorException extends \DomainException implements ExceptionInterface
{
    //TODO PDU ID
    public function __construct(string $message, int $status, \Throwable $previous = null)
    {
        parent::__construct($message, $status, $previous);
    }
}
