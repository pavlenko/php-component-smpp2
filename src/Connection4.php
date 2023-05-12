<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Deferred;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Util\Buffer;
use PE\Component\SMPP\Util\Decoder;
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
     * @var Deferred[]
     */
    private array $expects = [];
    private string $buffer = '';
    private int $status = ConnectionInterface::STATUS_CREATED;
    private ?SessionInterface $session = null;
    private int $lastMessageTime = 0;

    private ?string $clientAddress = null;
    private ?string $remoteAddress = null;

    public function __construct(
        SocketClientInterface $client,
        SerializerInterface $serializer,
        LoggerInterface $logger = null
    ) {
        $this->client = $client;
        $this->client->setInputHandler(function (string $data) {
            try {
                $this->buffer .= $data;

                $decoder = new Decoder();
                $reader = new Buffer($this->buffer);
                while ($reader->bytesLeft() >= 16) {
                    $length = $reader->shiftInt32();
                    if (strlen($this->buffer) >= $length) {
                        $reader->shiftBytes($length - 4);
                        //$pdu = $this->serializer->decode(substr($this->buffer, 4, $length));
                        $pdu = $decoder->decode(substr($this->buffer, 4, $length));

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

        $this->client->setCloseHandler(fn(string $message = null) => $this->close($message));

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

    public function getClientAddress(): ?string
    {
        if (null === $this->clientAddress) {
            $this->clientAddress = $this->client->getClientAddress();
        }
        return $this->clientAddress;
    }

    public function getRemoteAddress(): ?string
    {
        if (null === $this->remoteAddress) {
            $this->remoteAddress = $this->client->getRemoteAddress();
        }
        return $this->remoteAddress;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function getSession(): ?SessionInterface
    {
        return $this->session;
    }

    public function setSession(SessionInterface $session): void
    {
        $this->session = $session;
    }

    public function getExpects(): array
    {
        return $this->expects;
    }

    public function delExpects(int $seqNum, int $id): ?Deferred
    {
        foreach ($this->expects as $index => $expect) {
            if ($expect->isExpectPDU($seqNum, $id)) {
                $deferred = $this->expects[$index];
                unset($this->expects[$index]);
                return $deferred;
            }
        }
        return null;
    }

    public function getLastMessageTime(): int
    {
        return $this->lastMessageTime;
    }

    public function updLastMessageTime(): void
    {
        $this->lastMessageTime = time();
    }

    //TODO maybe pass instance instead of create inside
    public function wait(int $timeout, int $seqNum = 0, int ...$expectPDU): Deferred
    {
        $this->expects[] = $expects = new Deferred($timeout, $seqNum, ...$expectPDU);
        $this->logger->log(LogLevel::DEBUG, '? ' . $expects->toLogger());
        return $expects;
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
