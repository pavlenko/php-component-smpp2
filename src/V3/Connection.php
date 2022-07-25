<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Exception\TimeoutException;
use PE\Component\SMPP\Util\Stream;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Connection implements ConnectionInterface
{
    private Stream $stream;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;
    private int $status;

    public function __construct(Stream $stream, SerializerInterface $serializer = null, LoggerInterface $logger = null)
    {
        $this->stream     = $stream;
        $this->serializer = $serializer ?: new Serializer();
        $this->logger     = $logger ?: new NullLogger();
    }

    public function getStream(): Stream
    {
        return $this->stream;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
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

        $this->logger->log(LogLevel::DEBUG, __FUNCTION__);
        $buffer = $this->stream->readData(unpack('N', $length)[1] - 4);
        return $this->serializer->decode($buffer);
    }

    public function sendPDU(PDUInterface $pdu): void
    {
        $this->logger->log(LogLevel::DEBUG, __FUNCTION__);
        $this->stream->sendData($this->serializer->encode($pdu));
    }

    public function waitPDU(int $seqNum = 0, float $timeout = 0.01): PDUInterface
    {
        $this->logger->log(LogLevel::DEBUG, __FUNCTION__);
        do {
            $r = [$this->stream];
            $n = [];
            Stream::select($r, $n, $n, 0.01);

            if (!empty($r)) {
                $pdu = $this->readPDU();
                if (null !== $pdu) {
                    if (0 === $seqNum || $pdu->getSeqNum() === $seqNum) {
                        return $pdu;
                    }
                    if ($this->status & self::STATUS_BOUND_TRX) {
                        //TODO $this->storage->insert(0, $pdu);
                    }
                }
            }

            $timeout -= 0.001;
            usleep(1000);
        } while ($timeout > 0);

        throw new TimeoutException();
    }

    public function exit(): void
    {
//        if ($this->status !== self::STATUS_CLOSED) {
//            $this->status = self::STATUS_CLOSED;
            $this->stream->close();
//        }
    }
}
