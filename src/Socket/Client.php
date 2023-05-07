<?php

namespace PE\Component\SMPP\Socket;

use PE\Component\Stream\Exception\RuntimeException;

final class Client implements ClientInterface
{
    private \Closure $onInput;
    private \Closure $onError;
    private \Closure $onClose;

    private string $buffer = '';

    private SocketInterface $stream;
    private SelectInterface $select;

    public function __construct(SocketInterface $stream, SelectInterface $select)
    {
        $this->stream = $stream;
        $this->stream->setBlocking(false);
        $this->stream->setBufferRD(0);

        $this->select = $select;
        $this->select->attachStreamRD($stream->getResource(), function () {
            try {
                $data = $this->stream->recv();
            } catch (RuntimeException $exception) {
                call_user_func($this->onError, $exception);
                return;
            }

            if ($data !== '') {
                call_user_func($this->onInput, $data);
            } elseif ($this->stream->isEOF()) {
                $this->close('Disconnected on RD');
            }
        });

        $this->onError = fn() => null;// Dummy callback
        $this->onInput = fn() => null;// Dummy callback
        $this->onClose = fn() => null;// Dummy callback
    }

    public function getClientAddress(): ?string
    {
        return $this->stream->getAddress(false);
    }

    public function getRemoteAddress(): ?string
    {
        return $this->stream->getAddress(true);
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

    public function write(string $data): void
    {
        if (!is_resource($this->stream->getResource())) {
            $this->close('Disconnected on WR');
            return;
        }

        if (empty($data)) {
            return;
        }

        $this->buffer .= $data;
        $this->select->attachStreamWR($this->stream->getResource(), function () {
            try {
                $sent = $this->stream->send($this->buffer);
            } catch (RuntimeException $exception) {
                call_user_func($this->onError, $exception);
                return;
            }

            $this->buffer = substr($this->buffer, $sent);
            if (empty($this->buffer)) {
                $this->select->detachStreamWR($this->stream->getResource());
            }
        });
    }

    public function close(string $message = null): void
    {
        call_user_func($this->onClose, $message);
        $this->stream->close();
    }
}
