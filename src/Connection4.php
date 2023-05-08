<?php

namespace PE\Component\SMPP;

use PE\Component\Event\EmitterInterface;
use PE\Component\Event\Event;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Util\Buffer;
use PE\Component\SMPP\Util\SerializerInterface;
use PE\Component\Socket\ClientInterface as SocketClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Connection4
{
    public const EVT_INPUT = 'connection.input';

    private SocketClientInterface $client;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;

    public function __construct(
        SocketClientInterface $client,
        EmitterInterface $emitter,//TODO maybe pass callback instead of emitter
        SerializerInterface $serializer,
        LoggerInterface $logger = null
    ) {
        $this->client = $client;
        $this->client->setInputHandler(function (string $data) use ($emitter) {
            $buffer = new Buffer($data);
            $length = $buffer->shiftInt32();
            if (empty($length)) {
                $this->logger->log(LogLevel::WARNING, 'Unexpected data length');
                return;
            }
            $data = $buffer->shiftBytes($length);
            $pdu  = $this->serializer->decode($data);

            $this->logger->log(LogLevel::DEBUG, sprintf(
                '< PDU(0x%08X, 0x%08X, %d)',
                $pdu->getID(),
                $pdu->getStatus(),
                $pdu->getSeqNum()
            ));

            $emitter->dispatch(new Event(self::EVT_INPUT, $pdu));
        });

        $this->serializer = $serializer;
        $this->logger     = $logger ?: new NullLogger();
    }

    public function send(PDU $pdu, int $expectPDU = null, int $timeout = null): Request4
    {
        $this->logger->log(LogLevel::DEBUG, sprintf(
            '> PDU(0x%08X, 0x%08X, %d)',
            $pdu->getID(),
            $pdu->getStatus(),
            $pdu->getSeqNum()
        ));

        $this->client->write($this->serializer->encode($pdu));
        return new Request4($pdu, $expectPDU, $timeout);//TODO do not return, just store inside
    }

    public function close(string $message = null): void
    {
        $this->client->close($message);
    }
}
