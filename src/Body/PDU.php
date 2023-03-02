<?php

namespace PE\Component\SMPP\Body;

use PE\Component\SMPP\Util\Buffer;

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
        self::GENERIC_NACK          => GenericNack::class,
        self::BIND_RECEIVER         => BindReceiver::class,
        self::BIND_RECEIVER_RESP    => BindReceiverResp::class,
        self::BIND_TRANSMITTER      => BindTransmitter::class,
        self::BIND_TRANSMITTER_RESP => BindTransmitterResp::class,
        self::QUERY_SM              => QuerySm::class,
        self::QUERY_SM_RESP         => QuerySmResp::class,
        self::SUBMIT_SM             => SubmitSm::class,
        self::SUBMIT_SM_RESP        => SubmitSmResp::class,
        self::DELIVER_SM            => DeliverSm::class,
        self::DELIVER_SM_RESP       => DeliverSmResp::class,
        self::UNBIND                => Unbind::class,
        self::UNBIND_RESP           => UnbindResp::class,
        self::REPLACE_SM            => ReplaceSm::class,
        self::REPLACE_SM_RESP       => ReplaceSmResp::class,
        self::CANCEL_SM             => CancelSm::class,
        self::CANCEL_SM_RESP        => CancelSmResp::class,
        self::BIND_TRANSCEIVER      => BindTransceiver::class,
        self::BIND_TRANSCEIVER_RESP => BindReceiverResp::class,
        self::ENQUIRE_LINK          => EnquireLink::class,
        self::ENQUIRE_LINK_RESP     => EnquireLinkResp::class,
    ];

    private int $commandStatus = 0;
    private int $sequenceNum = 1;
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

    public function getSequenceNum(): int
    {
        return $this->sequenceNum;
    }

    public function setSequenceNum(int $sequenceNum): void
    {
        $this->sequenceNum = $sequenceNum;
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
        $buffer = new Buffer();
        $buffer->writeInt32($this->getCommandLength());
        $buffer->writeInt32($this->getCommandID());
        $buffer->writeInt32($this->getCommandStatus());
        $buffer->writeInt32($this->getSequenceNum());

        return $buffer . $this->getBody();
    }
}
