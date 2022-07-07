<?php

namespace PE\SMPP\PDU;

use PE\SMPP\Builder;
use PE\SMPP\Decoder;

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

        $decoder = new Decoder($body);
        $this->setSystemID($decoder->readString(16));
        $this->setPassword($decoder->readString(9));
        $this->setSystemType($decoder->readString(13));
        $this->setInterfaceVersion($decoder->readInt8());
        $this->setAddress($decoder->readAddress(41));
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
