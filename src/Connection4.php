<?php

namespace PE\Component\SMPP;

use PE\Component\Event\EmitterInterface;
use PE\Component\Event\Event;
use PE\Component\SMPP\DTO\ExpectsPDU;
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

    private \Closure $onInput;
    private \Closure $onError;

    private SocketClientInterface $client;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;

    /**
     * @var ExpectsPDU[]
     */
    private array $expects = [];
    private int $lastMessageTime = 0;

    public function __construct(
        SocketClientInterface $client,
        SerializerInterface $serializer,
        LoggerInterface $logger = null
    ) {
        $this->client = $client;
        $this->client->setInputHandler(function (string $data) {
            $buffer = new Buffer($data);
            $length = $buffer->shiftInt32();
            if (empty($length)) {
                $this->logger->log(LogLevel::WARNING, 'Unexpected data length');
                call_user_func($this->onError);
                return;
            }

            $pdu = $this->serializer->decode($buffer->shiftBytes($length));

            $this->logger->log(LogLevel::DEBUG, '< ' . $pdu->toLogger());
            call_user_func($this->onInput, $pdu);
            $this->updLastMessageTime();
        });

        $this->serializer = $serializer;
        $this->logger     = $logger ?: new NullLogger();

        $this->onInput = fn() => null;
        $this->onError = fn() => null;

        $this->updLastMessageTime();
    }

    public function setInputHandler(callable $handler): void
    {
        $this->onInput = \Closure::fromCallable($handler);
    }

    public function setErrorHandler(callable $handler): void
    {
        $this->onError = \Closure::fromCallable($handler);
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
        foreach ($this->expects as $index => $expect) {
            if ($expect->getSeqNum() === $seqNum || $expect->isExpectPDU($id)) {
                unset($this->expects[$index]);
            }
        }
    }

    public function getLastMessageTime(): int
    {
        return $this->lastMessageTime;
    }

    public function updLastMessageTime(): void
    {
        $this->lastMessageTime = time();
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
        $this->updLastMessageTime();
    }

    public function close(string $message = null): void
    {
        $this->client->setCloseHandler(fn() => null);
        $this->client->close($message);

        $this->onInput = fn() => null;
        $this->onError = fn() => null;
    }
}
