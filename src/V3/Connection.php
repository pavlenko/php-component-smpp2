<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Exception\ConnectionException;
use PE\Component\SMPP\Exception\TimeoutException;
use PE\Component\SMPP\PDU\Address;
use PE\Component\SMPP\Util\Stream;
use PE\Component\SMPP\Util\StreamException;

class Connection implements ConnectionInterface
{
    private FactoryInterface $factory;
    private int $status;
    private ?Stream $stream = null;

    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    public function open(): void
    {
        if ($this->status === self::STATUS_CREATED || $this->status === self::STATUS_CLOSED) {
            $this->status = self::STATUS_CREATED;
            $this->stream = Stream::createServer('127.0.0.1:2775');
        }
    }

    public function bind(int $type, string $systemID, string $password = null, Address $address = null): void
    {
        if ($this->status === self::STATUS_OPENED) {
            switch ($type) {
                case PDUInterface::ID_BIND_RECEIVER:
                    $resp = PDUInterface::ID_BIND_RECEIVER_RESP;
                    break;
                case PDUInterface::ID_BIND_TRANSMITTER:
                    $resp = PDUInterface::ID_BIND_TRANSMITTER_RESP;
                    break;
                case PDUInterface::ID_BIND_TRANSCEIVER:
                    $resp = PDUInterface::ID_BIND_TRANSCEIVER_RESP;
                    break;
                default:
                    throw new \UnexpectedValueException('Invalid bind type');
            }
            $this->sendPDU($type, 0, new PDU(/*[$systemID, $password, $address]*/));
            if (PDUInterface::STATUS_NO_ERROR !== $this->waitPDU($resp, 0)->getStatus()) {
                throw new ConnectionException();
            }
        }
    }

    public function readPDU(): ?PDUInterface
    {
        try {
            if ($this->stream->isEOF()) {
                return null;
            }

            $length = $this->stream->readData(4);
            if ('' === $length) {
                return null;
            }

            return $this->factory->createPDU($this->stream->readData($length - 4));
        } catch (StreamException $exception) {
            throw new ConnectionException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function sendPDU(int $commandID, int $seqNum, PDUInterface $pdu): void
    {
        try {
            $this->stream->sendData($pdu->encode($commandID, $seqNum));
        } catch (StreamException $exception) {
            throw new ConnectionException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function waitPDU(int $commandID, int $seqNum, float $timeout = 0.01): PDUInterface
    {
        do {
            if ($pdu = $this->readPDU()) {
                return $pdu;
            }

            $timeout -= 0.001;
            usleep(1000);
        } while ($timeout > 0);

        throw new TimeoutException();
    }

    public function exit(): void
    {
        if ($this->status !== self::STATUS_CLOSED) {
            $this->sendPDU(PDUInterface::ID_UNBIND, 0, new PDU());

            if (PDUInterface::STATUS_NO_ERROR !== $this->waitPDU(PDUInterface::ID_UNBIND_RESP, 0)->getStatus()) {
                echo "Unexpectedly unbind result - but just close\n";
            }

            $this->stream->close();
            $this->stream = null;
            $this->status = self::STATUS_CLOSED;
        }
    }
}
