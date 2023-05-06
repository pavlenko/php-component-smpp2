<?php

namespace PE\Component\SMPP\Socket;

use PE\Component\Stream\Exception\RuntimeException;
use PE\Component\Stream\SelectInterface;
use PE\Component\Stream\StreamInterface;

final class Server implements ServerInterface
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

    public function getAddress(): ?string
    {
        //TODO to base socket
        if (!is_resource($this->stream->getResource())) {
            return null;
        }

        $address = stream_socket_get_name($this->stream->getResource(), false);

        // check if this is an IPv6 address which includes multiple colons but no square brackets
        $pos = strrpos($address, ':');
        if (false !== $pos && strpos($address, ':') < $pos && substr($address, 0, 1) !== '[') {
            $address = '[' . substr($address, 0, $pos) . ']:' . substr($address, $pos + 1); // @codeCoverageIgnore
        }

        return 'tcp://' . $address;
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
