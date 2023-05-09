<?php

namespace PE\Component\SMPP;

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
    private \Closure $onInput;
    private \Closure $onError;
    private \Closure $onClose;

    private SocketClientInterface $client;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;

    /**
     * @var ExpectsPDU[]
     */
    private array $expects = [];
    private string $buffer = '';
    private int $status = ConnectionInterface::STATUS_CREATED;
    private int $lastMessageTime = 0;

    public function __construct(
        SocketClientInterface $client,
        SerializerInterface $serializer,
        LoggerInterface $logger = null
    ) {
        $this->client = $client;
        $this->client->setInputHandler(function (string $data) {
            try {
                $this->buffer .= $data;

                $reader = new Buffer($this->buffer);
                while ($reader->bytesLeft() >= 16) {
                    $length = $reader->shiftInt32();
                    if (strlen($this->buffer) >= $length) {
                        $reader->shiftBytes($length - 4);
                        $pdu = $this->serializer->decode(substr($this->buffer, 4, $length));

                        $this->buffer = substr($this->buffer, $length);
                        $this->logger->log(LogLevel::DEBUG, '< ' . $pdu->toLogger());

                        call_user_func($this->onInput, $pdu);
                        $this->updLastMessageTime();
                    }
                }
            } catch (\Throwable $exception) {
                $this->logger->log(LogLevel::ERROR, 'E: ' . $exception);
                call_user_func($this->onError, $exception);
                $this->close();
            }
        });

        $this->serializer = $serializer;
        $this->logger     = $logger ?: new NullLogger();

        $this->onInput = fn() => null;
        $this->onError = fn() => null;
        $this->onClose = fn() => null;

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

    public function setCloseHandler(callable $handler): void
    {
        $this->onClose = \Closure::fromCallable($handler);
    }

    public function getClient(): SocketClientInterface
    {
        return $this->client;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
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
        call_user_func($this->onClose, $message);

        $this->client->setCloseHandler(fn() => null);
        $this->client->close($message);

        $this->onInput = fn() => null;
        $this->onError = fn() => null;
        $this->onClose = fn() => null;
    }
}
