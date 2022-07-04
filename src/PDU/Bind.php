<?php

namespace PE\SMPP\PDU;

use alexeevdv\React\Smpp\Utils\DataWrapper;

abstract class Bind extends PDU
{
    private string $systemType;
    private string $systemID;
    private string $password;
    private int $interfaceVersion;
    private ?Address $address;

    public function __construct($body = '')
    {
        parent::__construct($body);
        if (strlen($body) === 0) {
            return;
        }

        $wrapper = new DataWrapper($body);
        $this->setSystemId(
            $wrapper->readNullTerminatedString(16)
        );
        $this->setPassword(
            $wrapper->readNullTerminatedString(9)
        );
        $this->setSystemType(
            $wrapper->readNullTerminatedString(13)
        );
        $this->setInterfaceVersion(
            $wrapper->readInt8()
        );
        $this->setAddress(
            $wrapper->readAddress(41)
        );
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
        $builder = new Builder();
        $builder->addString($this->getSystemID());
        $builder->addString($this->getPassword());
        $builder->addString($this->getSystemType());
        $builder->addInt8($this->getInterfaceVersion());
        $builder->addAddress($this->getAddress());

        $this->setBody((string) $builder);
        return parent::__toString();
    }
}
