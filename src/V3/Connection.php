<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Exception\TimeoutException;
use PE\Component\SMPP\Util\Stream;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class Connection implements ConnectionInterface
{
    private SerializerInterface $serializer;
    private StorageInterface $storage;
    private LoggerInterface $logger;
    private int $status;
    private int $seqNum;
    private ?Stream $stream = null;

    public function __construct(SerializerInterface $serializer, StorageInterface $storage, LoggerInterface $logger = null)
    {
        $this->serializer = $serializer;
        $this->storage    = $storage;
        $this->logger     = $logger ?: new NullLogger();

        // Generate random sequence number for make connection more unique
        $this->seqNum = random_int(0x001, 0x7FF) << 20;
    }

    //- client/sender (open + bind + exit + unbind)
    //- server (open + accept + exit)
    //- server child (exit + unbind)
    public function open(): void
    {
        if ($this->status === self::STATUS_CREATED || $this->status === self::STATUS_CLOSED) {
            $this->status = self::STATUS_CREATED;
            $this->stream = Stream::createServer('127.0.0.1:2775');// specific by server/sender/client, how to???
        }
    }

    public function bind(int $type, SessionInterface $session): void
    {
        if ($this->status === self::STATUS_OPENED) {
            if (!array_key_exists($type, self::BOUND_MAP)) {
                throw new \UnexpectedValueException('Unexpected bind type');
            }

            $this->status = self::BOUND_MAP[$type];
            $this->seqNum++;
            $this->sendPDU(new PDU($type, PDUInterface::STATUS_NO_ERROR, $this->seqNum, [
                'system_id'         => $session->getSystemID(),
                'password'          => $session->getPassword(),
                'system_type'       => '',
                'interface_version' => self::INTERFACE_VER,
                'address'           => $session->getAddress(),
            ]));

            if (PDUInterface::STATUS_NO_ERROR !== $this->waitPDU($this->seqNum)->getStatus()) {
                throw new \UnexpectedValueException('Unexpected bind response');
            }
        }
    }

    public function readPDU(): ?PDUInterface
    {
        if ($this->stream->isEOF()) {
            $this->logger->log(LogLevel::WARNING, 'Connection closed by remote');
            return null;
        }

        $length = $this->stream->readData(4);
        if ('' === $length) {
            $this->logger->log(LogLevel::WARNING, 'Unexpected data length');
            return null;
        }

        return $this->serializer->decode($this->stream->readData($length - 4));
    }

    public function sendPDU(PDUInterface $pdu): void
    {
        $this->stream->sendData($this->serializer->encode($pdu));
    }

    public function waitPDU(int $seqNum, float $timeout = 0.01): PDUInterface
    {
        do {
            $pdu = $this->readPDU();
            if (null !== $pdu) {
                if (0 === $seqNum || $pdu->getSeqNum() === $seqNum) {
                    return $pdu;
                }
                if ($this->status & self::STATUS_BOUND_TRX) {
                    $this->storage->insert(0, $pdu);
                }
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
            $this->sendPDU(new PDU(PDUInterface::ID_UNBIND, PDUInterface::STATUS_NO_ERROR, $this->seqNum));

            if (PDUInterface::STATUS_NO_ERROR !== $this->waitPDU($this->seqNum)->getStatus()) {
                $this->logger->log(LogLevel::WARNING, 'Unexpected response status, but just close');
            }

            $this->stream->close();
            $this->stream = null;
        }
    }
}
