<?php

namespace PE\Component\SMPP\Exception;

final class DecoderException extends \UnexpectedValueException implements ExceptionInterface
{
    public function __construct(string $message, int $status = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $status, $previous);
    }
}
