<?php

namespace PE\Component\SMPP\Socket;

use PE\Component\Stream\Exception\RuntimeException;
use PE\Component\Stream\SelectInterface;
use PE\Component\Stream\StreamInterface;

final class SocketServer implements SocketServerInterface
{
    private \Closure $onInput;
    private \Closure $onError;
    private \Closure $onClose;

    private StreamInterface $stream;

    public function __construct(StreamInterface $stream, SelectInterface $select, FactoryInterface $factory)
    {
        $this->stream = $stream;
        $this->stream->setBlocking(false);
        $this->stream->setBufferRD(0);

        //TODO this can be moved to factory
        $select->attachStreamRD($stream, function () use ($factory) {
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

    public function onInput(callable $handler): void
    {
        $this->onInput = \Closure::fromCallable($handler);
    }

    public function onError(callable $handler): void
    {
        $this->onError = \Closure::fromCallable($handler);
    }

    public function onClose(callable $handler): void
    {
        $this->onClose = \Closure::fromCallable($handler);
    }

    public function close(string $message = null): void
    {
        call_user_func($this->onClose, $message);
    }
}