<?php

namespace PE\Component\SMPP\Socket;

use PE\Component\Stream\Exception\RuntimeException;
use PE\Component\Stream\SelectInterface;
use PE\Component\Stream\StreamInterface;

final class Client implements ClientInterface
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

    public function getClientAddress(): ?string
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

    public function getRemoteAddress(): ?string
    {
        //TODO to base socket
        if (!is_resource($this->stream->getResource())) {
            return null;
        }

        $address = stream_socket_get_name($this->stream->getResource(), true);

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
