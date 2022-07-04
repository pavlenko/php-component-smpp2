<?php

namespace PE\SMPP\PDU;

class SubmitSm extends PDU
{
    private string $serviceType = '';
    private Address $sourceAddress;
    private Address $destinationAddress;
    private int $dataCoding = 0;//TODO DataCoding::DEFAULT;
    private string $shortMessage = '';
    private int $esmClass = 0;
    private ?\DateTimeImmutable $validityPeriod;
    private ?\DateTimeImmutable $scheduleDeliveryTime;
    private int $registeredDelivery = 1;
    private int $protocolId = 0;
    private int $priorityFlag = 0;
    private int $replaceIfPresentFlag = 0;
    private int $smDefaultMsgId = 0;

    public function getCommandID(): int
    {
        return 0x00000004;
    }

    public function getServiceType(): string
    {
        return $this->serviceType;
    }

    public function setServiceType(string $serviceType): void
    {
        $this->serviceType = $serviceType;
    }

    public function getSourceAddress(): ?Address
    {
        return $this->sourceAddress;
    }

    public function setSourceAddress(?Address $address): void
    {
        $this->sourceAddress = $address;
    }

    public function getDestinationAddress(): Address
    {
        return $this->destinationAddress;
    }

    public function setDestinationAddress(Address $address): void
    {
        $this->destinationAddress = $address;
    }

    public function getEsmClass(): int
    {
        return $this->esmClass;
    }

    public function setEsmClass(int $esmClass): void
    {
        $this->esmClass = $esmClass;
    }

    public function getDataCoding(): int
    {
        return $this->dataCoding;
    }

    public function setDataCoding(int $dataCoding): void
    {
        $this->dataCoding = $dataCoding;
    }

    public function getShortMessage(): string
    {
        return $this->shortMessage;
    }

    public function setShortMessage(string $shortMessage): void
    {
        $this->shortMessage = $shortMessage;
    }

    public function getValidityPeriod(): ?\DateTimeImmutable
    {
        return $this->validityPeriod;
    }

    public function setValidityPeriod(?\DateTimeImmutable $validityPeriod): void
    {
        $this->validityPeriod = $validityPeriod;
    }

    public function getScheduleDeliveryTime(): ?\DateTimeImmutable
    {
        return $this->scheduleDeliveryTime;
    }

    public function setScheduleDeliveryTime(?\DateTimeImmutable $scheduleDeliveryTime): void
    {
        $this->scheduleDeliveryTime = $scheduleDeliveryTime;
    }

    public function getRegisteredDelivery(): int
    {
        return $this->registeredDelivery;
    }

    public function setRegisteredDelivery(int $registeredDelivery): void
    {
        $this->registeredDelivery = $registeredDelivery;
    }

    public function getProtocolId(): int
    {
        return $this->protocolId;
    }

    public function setProtocolId(int $protocolId): void
    {
        $this->protocolId = $protocolId;
    }

    public function getPriorityFlag(): int
    {
        return $this->priorityFlag;
    }

    public function setPriorityFlag(int $priorityFlag): void
    {
        $this->priorityFlag = $priorityFlag;
    }

    public function getReplaceIfPresentFlag(): int
    {
        return $this->replaceIfPresentFlag;
    }

    public function setReplaceIfPresentFlag(int $replaceIfPresentFlag): void
    {
        $this->replaceIfPresentFlag = $replaceIfPresentFlag;
    }

    public function getSmDefaultMsgId(): int
    {
        return $this->smDefaultMsgId;
    }

    public function setSmDefaultMsgId(int $smDefaultMsgId): void
    {
        $this->smDefaultMsgId = $smDefaultMsgId;
    }

    public function __toString(): string
    {
        $builder = new Builder();
        $builder->addString($this->getServiceType());
        $builder->addAddress($this->getSourceAddress());
        $builder->addAddress($this->getDestinationAddress());
        $builder->addInt8( $this->getEsmClass());
        $builder->addInt8($this->getProtocolId());
        $builder->addInt8($this->getPriorityFlag());
        $builder->addDateTime($this->getScheduleDeliveryTime());
        $builder->addDateTime($this->getValidityPeriod());
        $builder->addInt8($this->getRegisteredDelivery());
        $builder->addInt8($this->getReplaceIfPresentFlag());
        $builder->addInt8($this->getDataCoding());
        $builder->addInt8($this->getSmDefaultMsgId());
        $builder->addInt8(strlen($this->getShortMessage()));
        $builder->addBytes($this->getShortMessage());

        $this->setBody((string) $builder);
        return parent::__toString();
    }
}
