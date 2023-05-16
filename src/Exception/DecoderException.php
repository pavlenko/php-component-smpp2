<?php

namespace PE\Component\SMPP\Exception;

final class DecoderException extends \UnexpectedValueException implements ExceptionInterface
{
    private int $commandID = 0;

    public function __construct(string $message, \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function getCommandID(): int
    {
        return $this->commandID;
    }

    public function setCommandID(int $commandID): void
    {
        $this->commandID = $commandID;
    }
}
