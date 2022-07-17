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
    private StorageInterface $storage;
    private int $status;
    private int $seqNum;
    private ?Stream $stream = null;

    public function __construct(FactoryInterface $factory, StorageInterface $storage)
    {
        $this->factory = $factory;
        $this->storage = $storage;

        // Generate random sequence number for make connection more unique
        $this->seqNum = random_int(0x001, 0x7FF) << 20;
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
            if (!array_key_exists($type, self::BOUND_MAP)) {
                throw new \UnexpectedValueException('Invalid bind type');
            }

            $this->status = self::BOUND_MAP[$type];
            $this->seqNum++;
            $this->sendPDU(new PDU($type, 0, $this->seqNum, /*[$systemID, $password, $address]*/));

            if (PDUInterface::STATUS_NO_ERROR !== $this->waitPDU($this->seqNum)->getStatus()) {
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

    public function sendPDU(PDUInterface $pdu): void
    {
        try {
            $this->stream->sendData($pdu);
        } catch (StreamException $exception) {
            throw new ConnectionException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    public function waitPDU(int $seqNum, float $timeout = 0.01): PDUInterface
    {
        do {
            $pdu = $this->readPDU();
            if (null !== $pdu) {
                if ($pdu->getSeqNum() === $seqNum) {
                    return $pdu;
                }
                $this->storage->add($pdu);
            }

            $timeout -= 0.001;
            usleep(1000);
        } while ($timeout > 0);

        throw new TimeoutException();
    }

    public function exit(): void
    {
        if ($this->status !== self::STATUS_CLOSED) {
            $this->status = self::STATUS_CLOSED;

            $this->seqNum++;
            $this->sendPDU(new PDU(PDUInterface::ID_UNBIND, 0, $this->seqNum));

            if (PDUInterface::STATUS_NO_ERROR !== $this->waitPDU($this->seqNum)->getStatus()) {
                echo "Unexpectedly unbind result - but just close\n";
            }

            $this->stream->close();
            $this->stream = null;
        }
    }
}
