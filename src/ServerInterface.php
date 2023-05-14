<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\PDU;

interface ServerInterface
{
    //TODO to connection
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
     * Listen to socket
     *
     * @param string $address
     * @return void
     */
    public function bind(string $address): void;

    /**
     * Close all sessions & stop server
     */
    public function stop(): void;
}
