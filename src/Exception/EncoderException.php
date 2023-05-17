<?php

namespace PE\Component\SMPP\Exception;

final class EncoderException extends \UnexpectedValueException implements ExceptionInterface
{
    public function __construct(string $message, int $errorCode = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $errorCode, $previous);
    }
}
