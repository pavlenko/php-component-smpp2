<?php

namespace PE\Component\SMPP\Socket;

use PE\Component\Stream\Exception\RuntimeException;
use PE\Component\Stream\SelectInterface;
use PE\Component\Stream\StreamInterface;

final class SocketClient implements SocketClientInterface
{
    private \Closure $onInput;
    private \Closure $onError;
    private \Closure $onClose;

    private string $buffer;

    private StreamInterface $stream;
    private SelectInterface $select;

    public function __construct(StreamInterface $stream, SelectInterface $select)
    {
        $this->stream = $stream;
        $this->stream->setBlocking(false);
        $this->stream->setBufferRD(0);

        $this->select = $select;
        $this->select->attachStreamRD($stream, function () {
            try {
                $data = $this->stream->recvData();
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
        $this->select->attachStreamWR($this->stream, function () {
            try {
                $sent = $this->stream->sendData($this->buffer);
            } catch (RuntimeException $exception) {
                call_user_func($this->onError, $exception);
                return;
            }

            $this->buffer = substr($this->buffer, $sent);
            if (empty($this->buffer)) {
                $this->select->detachStreamWR($this->stream);
            }
        });
    }

    public function close(string $message = null): void
    {
        call_user_func($this->onClose, $message);
        $this->stream->close();
    }
}
