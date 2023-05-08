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

    /**
     * @var ExpectsPDU[]
     */
    private array $expects = [];

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

            $this->logger->log(LogLevel::DEBUG, '< ' . $pdu->toLogger());
            $emitter->dispatch(new Event(self::EVT_INPUT, $this, $pdu));
        });

        $this->serializer = $serializer;
        $this->logger     = $logger ?: new NullLogger();
    }

    public function getClient(): SocketClientInterface
    {
        return $this->client;
    }

    public function getExpects(): array
    {
        return $this->expects;
    }

    public function delExpects(int $seqNum, int $id): void
    {
        foreach ($this->expects as $expect) {
            if ($expect->getSeqNum() === $seqNum || $expect->isExpectPDU($id)) {
                unset($this->expects[$seqNum]);
            }
        }
    }

    public function wait(int $timeout, int $seqNum = 0, int ...$expectPDU): void
    {
        $this->expects[] = $expects = new ExpectsPDU($timeout, $seqNum, ...$expectPDU);
        $this->logger->log(LogLevel::DEBUG, '? ' . $expects->toLogger());
    }

    public function send(PDU $pdu): void
    {
        $this->logger->log(LogLevel::DEBUG, '> ' . $pdu->toLogger());
        $this->client->write($this->serializer->encode($pdu));
    }

    public function close(string $message = null): void
    {
        $this->client->setCloseHandler(fn() => null);
        $this->client->close($message);
    }
}
