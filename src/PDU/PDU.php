<?php

namespace PE\SMPP\PDU;

use PE\SMPP\Builder;

abstract class PDU
{
    public const GENERIC_NACK          = 0x80000000;
    public const BIND_RECEIVER         = 0x00000001;
    public const BIND_RECEIVER_RESP    = 0x80000001;
    public const BIND_TRANSMITTER      = 0x00000002;
    public const BIND_TRANSMITTER_RESP = 0x80000002;
    public const QUERY_SM              = 0x00000003;
    public const QUERY_SM_RESP         = 0x80000003;
    public const SUBMIT_SM             = 0x00000004;
    public const SUBMIT_SM_RESP        = 0x80000004;
    public const DELIVER_SM            = 0x00000005;
    public const DELIVER_SM_RESP       = 0x80000005;
    public const UNBIND                = 0x00000006;
    public const UNBIND_RESP           = 0x80000006;
    public const REPLACE_SM            = 0x00000007;
    public const REPLACE_SM_RESP       = 0x80000007;
    public const CANCEL_SM             = 0x00000008;
    public const CANCEL_SM_RESP        = 0x80000008;
    public const BIND_TRANSCEIVER      = 0x00000009;
    public const BIND_TRANSCEIVER_RESP = 0x80000009;
    public const ENQUIRE_LINK          = 0x00000015;
    public const ENQUIRE_LINK_RESP     = 0x80000015;

    public const CLASS_MAP = [
/*public const GENERIC_NACK          = */ 0x80000000 => GenericNack::class,
/*public const BIND_RECEIVER         = */ 0x00000001 => BindReceiver::class,
/*public const BIND_RECEIVER_RESP    = */ 0x80000001 => BindReceiverResp::class,
/*public const BIND_TRANSMITTER      = */ 0x00000002 => BindTransmitter::class,
/*public const BIND_TRANSMITTER_RESP = */ 0x80000002 => BindTransmitterResp::class,
/*public const QUERY_SM              = */ 0x00000003 => null,
/*public const QUERY_SM_RESP         = */ 0x80000003 => null,
/*public const SUBMIT_SM             = */ 0x00000004 => null,
/*public const SUBMIT_SM_RESP        = */ 0x80000004 => null,
/*public const DELIVER_SM            = */ 0x00000005 => null,
/*public const DELIVER_SM_RESP       = */ 0x80000005 => null,
/*public const UNBIND                = */ 0x00000006 => null,
/*public const UNBIND_RESP           = */ 0x80000006 => null,
/*public const REPLACE_SM            = */ 0x00000007 => null,
/*public const REPLACE_SM_RESP       = */ 0x80000007 => null,
/*public const CANCEL_SM             = */ 0x00000008 => null,
/*public const CANCEL_SM_RESP        = */ 0x80000008 => null,
/*public const BIND_TRANSCEIVER      = */ 0x00000009 => null,
/*public const BIND_TRANSCEIVER_RESP = */ 0x80000009 => null,
/*public const ENQUIRE_LINK          = */ 0x00000015 => null,
/*public const ENQUIRE_LINK_RESP     = */ 0x80000015 => null,
    ];

    private int $commandStatus = 0;
    private int $sequenceNumber = 1;
    private string $body;

    public function __construct(string $body = '')
    {
        $this->body = $body;
    }

    abstract public function getCommandID(): int;

    public function getCommandLength(): int
    {
        return 16 + strlen($this->getBody());
    }

    public function getCommandStatus(): int
    {
        return $this->commandStatus;
    }

    public function setCommandStatus(int $status): void
    {
        $this->commandStatus = $status;
    }

    public function getSequenceNumber(): int
    {
        return $this->sequenceNumber;
    }

    public function setSequenceNumber(int $sequenceNumber): void
    {
        $this->sequenceNumber = $sequenceNumber;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function setBody(string $body): void
    {
        $this->body = $body;
    }

    public function __toString(): string
    {
        $builder = new Builder();
        $builder->addInt32($this->getCommandLength());
        $builder->addInt32($this->getCommandID());
        $builder->addInt32($this->getCommandStatus());
        $builder->addInt32($this->getSequenceNumber());

        return $builder . $this->getBody();
    }
}
