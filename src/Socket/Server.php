<?php

namespace PE\Component\SMPP\Socket;

use PE\Component\Stream\Exception\RuntimeException;

final class Server implements ServerInterface
{
    private \Closure $onInput;
    private \Closure $onError;
    private \Closure $onClose;

    private SocketInterface $stream;

    public function __construct(SocketInterface $stream, SelectInterface $select, FactoryInterface $factory)
    {
        $this->stream = $stream;
        $this->stream->setBlocking(false);
        $this->stream->setBufferRD(0);

        $select->attachStreamRD($stream->getResource(), function () use ($factory) {
            try {
                $client = $factory->acceptClient($this->stream);
                call_user_func($this->onInput, $client);
            } catch (RuntimeException $exception) {
                call_user_func($this->onError, $exception, $this);
            }
        });

        $this->onError = fn() => null;// Dummy callback
        $this->onInput = fn() => null;// Dummy callback
        $this->onClose = fn() => null;// Dummy callback
    }

    public function getAddress(): ?string
    {
        return $this->stream->getAddress(false);
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

    public function close(string $message = null): void
    {
        call_user_func($this->onClose, $message);
        $this->stream->close();
    }
}
