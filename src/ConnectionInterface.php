<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Deferred;
use PE\Component\SMPP\DTO\PDU;

interface ConnectionInterface
{
    public const INTERFACE_VER = 0x34;

    public const STATUS_OPENED    = 0b000;
    public const STATUS_BOUND_TX  = 0b001;
    public const STATUS_BOUND_RX  = 0b010;
    public const STATUS_BOUND_TRX = 0b011;
    public const STATUS_CLOSED    = 0b100;

    public const BIND_MAP = [
        PDU::ID_BIND_RECEIVER    => self::STATUS_BOUND_TX,
        PDU::ID_BIND_TRANSMITTER => self::STATUS_BOUND_RX,
        PDU::ID_BIND_TRANSCEIVER => self::STATUS_BOUND_TRX,
    ];

    public const BOUND_MAP = [
        PDU::ID_BIND_RECEIVER_RESP    => self::STATUS_BOUND_TX,
        PDU::ID_BIND_TRANSMITTER_RESP => self::STATUS_BOUND_RX,
        PDU::ID_BIND_TRANSCEIVER_RESP => self::STATUS_BOUND_TRX,
    ];

    public const ALLOWED_ID_BY_BOUND = [
        PDU::ID_GENERIC_NACK => [//from both
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_RX,
            ConnectionInterface::STATUS_BOUND_TRX,
        ],
        PDU::ID_BIND_RECEIVER => [//from client
            ConnectionInterface::STATUS_OPENED,
        ],
        PDU::ID_BIND_RECEIVER_RESP => [//from server
            ConnectionInterface::STATUS_OPENED,
        ],
        PDU::ID_BIND_TRANSMITTER => [//from client
            ConnectionInterface::STATUS_OPENED,
        ],
        PDU::ID_BIND_TRANSMITTER_RESP => [//from server
            ConnectionInterface::STATUS_OPENED,
        ],
        PDU::ID_QUERY_SM => [//from client
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_TRX,
        ],
        PDU::ID_QUERY_SM_RESP => [//from server
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_TRX,
        ],
        PDU::ID_SUBMIT_SM => [//from client
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_TRX,
        ],
        PDU::ID_SUBMIT_SM_RESP => [//from server
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_RX,
            ConnectionInterface::STATUS_BOUND_TRX,
        ],
        PDU::ID_DELIVER_SM => [//from server
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_RX,
            ConnectionInterface::STATUS_BOUND_TRX,
        ],
        PDU::ID_DELIVER_SM_RESP => [//from client
            ConnectionInterface::STATUS_BOUND_RX,
            ConnectionInterface::STATUS_BOUND_TRX,
        ],
        PDU::ID_UNBIND => [//from both
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_RX,
            ConnectionInterface::STATUS_BOUND_TRX,
        ],
        PDU::ID_UNBIND_RESP => [//from both
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_RX,
            ConnectionInterface::STATUS_BOUND_TRX,
        ],
        PDU::ID_REPLACE_SM => [//from client
            ConnectionInterface::STATUS_BOUND_TX,
        ],
        PDU::ID_REPLACE_SM_RESP => [//from client
            ConnectionInterface::STATUS_BOUND_TX,
        ],
        PDU::ID_CANCEL_SM => [//from client
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_TRX,
        ],
        PDU::ID_CANCEL_SM_RESP => [//from server
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_TRX,
        ],
        PDU::ID_BIND_TRANSCEIVER => [//from client
            ConnectionInterface::STATUS_OPENED,
        ],
        PDU::ID_BIND_TRANSCEIVER_RESP => [//from server
            ConnectionInterface::STATUS_OPENED,
        ],
        PDU::ID_OUT_BIND => [//from server
            ConnectionInterface::STATUS_OPENED,
        ],
        PDU::ID_ENQUIRE_LINK => [//from both
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_RX,
            ConnectionInterface::STATUS_BOUND_TRX,
        ],
        PDU::ID_ENQUIRE_LINK_RESP => [//from both
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_RX,
            ConnectionInterface::STATUS_BOUND_TRX,
        ],
        PDU::ID_SUBMIT_MULTI => [//from client
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_TRX,
        ],
        PDU::ID_SUBMIT_MULTI_RESP => [//from server
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_TRX,
        ],
        PDU::ID_ALERT_NOTIFICATION => [//from server
            ConnectionInterface::STATUS_BOUND_RX,
            ConnectionInterface::STATUS_BOUND_TRX
        ],
        PDU::ID_DATA_SM => [//from both
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_RX,
            ConnectionInterface::STATUS_BOUND_TRX
        ],
        PDU::ID_DATA_SM_RESP => [//from both
            ConnectionInterface::STATUS_BOUND_TX,
            ConnectionInterface::STATUS_BOUND_RX,
            ConnectionInterface::STATUS_BOUND_TRX
        ],
    ];

    /**
     * Set input handler
     *
     * @param callable $handler
     */
    public function setInputHandler(callable $handler): void;

    /**
     * Set error handler
     *
     * @param callable $handler
     */
    public function setErrorHandler(callable $handler): void;

    /**
     * Set close handler
     *
     * @param callable $handler
     */
    public function setCloseHandler(callable $handler): void;

    /**
     * Get cached client address
     *
     * @return string|null
     */
    public function getClientAddress(): ?string;

    /**
     * Get cached remote address
     *
     * @return string|null
     */
    public function getRemoteAddress(): ?string;

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus(): int;

    /**
     * Set status
     *
     * @param int $status
     */
    public function setStatus(int $status): void;

    /**
     * Get bound session
     *
     * @return SessionInterface|null
     */
    public function getSession(): ?SessionInterface;

    /**
     * Set bound session
     *
     * @param SessionInterface $session
     */
    public function setSession(SessionInterface $session): void;

    /**
     * Get last incoming/outgoing message timestamp
     *
     * @return int
     */
    public function getLastMessageTime(): int;

    /**
     * Set last incoming/outgoing message timestamp to now
     *
     * @return void
     */
    public function updLastMessageTime(): void;

    /**
     * Get wait queue for check timed-out packets
     *
     * @return Deferred[]
     */
    public function getWaitQueue(): array;

    /**
     * Search and dequeue packet from wait queue
     *
     * @param int $seqNum
     * @param int $id
     * @return Deferred|null
     */
    public function dequeuePacket(int $seqNum, int $id): ?Deferred;

    /**
     * Wait for incoming PDU based on expected sequence number and/or expected PDU identifiers
     *
     * @param int $timeout
     * @param int $seqNum
     * @param int ...$expectPDU
     * @return Deferred
     */
    public function wait(int $timeout, int $seqNum = 0, int ...$expectPDU): Deferred;

    /**
     * Send PDU
     *
     * @param PDU $pdu
     * @param bool $close Close after write
     */
    public function send(PDU $pdu, bool $close = false): void;

    /**
     * Set error message
     *
     * @param string|null $message
     */
    public function error(string $message = null): void;

    /**
     * Close connection with optional reason message
     *
     * @param string|null $message
     */
    public function close(string $message = null): void;
}
