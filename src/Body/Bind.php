<?php

namespace PE\Component\SMPP\Body;

use PE\Component\SMPP\Address;
use PE\Component\SMPP\Util\Buffer;

abstract class Bind extends PDU
{
    private string $systemType = '';
    private string $systemID = '';
    private string $password = '';
    private int $interfaceVersion = 0;
    private ?Address $address = null;

    public function __construct($body = '')
    {
        parent::__construct($body);
        if (strlen($body) === 0) {
            return;
        }

        $buffer = new Buffer($body);
        $this->setSystemID($buffer->shiftString(16));
        $this->setPassword($buffer->shiftString(9));
        $this->setSystemType($buffer->shiftString(13));
        $this->setInterfaceVersion($buffer->shiftInt8());
        $this->setAddress($buffer->shiftAddress(41));
    }

    public function getSystemType(): string
    {
        return $this->systemType;
    }

    public function setSystemType(string $systemType): void
    {
        $this->systemType = $systemType;
    }

    public function getSystemID(): string
    {
        return $this->systemID;
    }

    public function setSystemID(string $systemID): void
    {
        $this->systemID = $systemID;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getInterfaceVersion(): int
    {
        return $this->interfaceVersion;
    }

    public function setInterfaceVersion(int $interfaceVersion): void
    {
        $this->interfaceVersion = $interfaceVersion;
    }

    public function getAddress(): ?Address
    {
        return $this->address;
    }

    public function setAddress(?Address $address): void
    {
        $this->address = $address;
    }

    public function __toString(): string
    {
        $buffer = new Buffer();
        $buffer->writeString($this->getSystemID());
        $buffer->writeString($this->getPassword());
        $buffer->writeString($this->getSystemType());
        $buffer->writeInt8($this->getInterfaceVersion());
        $buffer->writeAddress($this->getAddress());

        $this->setBody((string) $buffer);
        return parent::__toString();
    }
}
