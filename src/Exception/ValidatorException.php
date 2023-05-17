<?php

namespace PE\Component\SMPP\Exception;

final class ValidatorException extends \DomainException implements ExceptionInterface
{
    private int $commandID;
    private int $errorCode;

    public function __construct(int $commandID, int $errorCode, string $message)
    {
        $this->commandID = $commandID;
        $this->errorCode = $errorCode;
        parent::__construct($message);
    }

    public function getCommandID(): int
    {
        return $this->commandID;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }
}
