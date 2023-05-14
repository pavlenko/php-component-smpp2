<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Deferred;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Exception\ExceptionInterface;
use PE\Component\SMPP\Util\DecoderInterface;
use PE\Component\SMPP\Util\EncoderInterface;
use PE\Component\Socket\ClientInterface as SocketClientInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Connection4 implements ConnectionInterface
{
    private \Closure $onInput;
    private \Closure $onError;
    private \Closure $onClose;

    private SocketClientInterface $client;
    private DecoderInterface $decoder;
    private EncoderInterface $encoder;
    private LoggerInterface $logger;

    /**
     * @var Deferred[]
     */
    private array $waitQueue = [];
    private string $buffer = '';
    private int $status = ConnectionInterface::STATUS_OPENED;
    private ?SessionInterface $session = null;
    private int $lastMessageTime = 0;

    private ?string $clientAddress = null;
    private ?string $remoteAddress = null;

    public function __construct(
        SocketClientInterface $client,
        DecoderInterface $decoder,
        EncoderInterface $encoder,
        LoggerInterface $logger = null
    ) {
        $this->client = $client;
        $this->client->setInputHandler(function (string $data) {
            try {
                $this->processReceive($data);
            } catch (ExceptionInterface $exception) {
                $this->logger->log(LogLevel::ERROR, 'E: ' . $exception->getMessage());
                call_user_func($this->onError, $exception);
            }
        });

        $this->client->setCloseHandler(fn(string $message = null) => $this->close($message));

        $this->decoder = $decoder;
        $this->encoder = $encoder;
        $this->logger  = $logger ?: new NullLogger();

        $this->onInput = fn() => null;
        $this->onError = fn() => null;
        $this->onClose = fn() => null;

        $this->updLastMessageTime();
    }

    private function processReceive(string $data): void
    {
        $this->buffer .= $data;

        while (strlen($this->buffer) >= 16) {
            $length = unpack('N', $this->buffer)[1];
            if (strlen($this->buffer) >= $length) {
                $buffer       = substr($this->buffer, 4, $length);
                $this->buffer = substr($this->buffer, $length);

                $pdu = $this->decoder->decode($buffer);

                $this->logger->log(LogLevel::DEBUG, 'I: ' . $pdu->toLogger());

                call_user_func($this->onInput, $pdu);
                $this->updLastMessageTime();
            }// Else wait for more data
        }
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

    public function getWaitQueue(): array
    {
        return $this->waitQueue;
    }

    public function dequeuePacket(int $seqNum, int $id): ?Deferred
    {
        foreach ($this->waitQueue as $index => $expect) {
            if ($expect->isExpectPDU($seqNum, $id)) {
                $deferred = $this->waitQueue[$index];
                unset($this->waitQueue[$index]);
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

    public function wait(int $timeout, int $seqNum = 0, int ...$expectPDU): Deferred
    {
        $deferred = new Deferred($timeout, $seqNum, ...$expectPDU);
        $this->logger->log(LogLevel::DEBUG, '?: ' . $deferred->dump());
        return $this->waitQueue[] = $deferred;
    }

    public function send(PDU $pdu): void
    {
        $this->logger->log(LogLevel::DEBUG, 'O: ' . $pdu->toLogger());
        $this->client->write($this->encoder->encode($pdu));
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
