<?php

namespace PE\Component\SMPP\Socket;

use PE\Component\Stream\Exception\RuntimeException;

final class Select implements SelectInterface
{
    public const DEFAULT_TIMEOUT_MS = 1000;

    private ?int $timeoutMs;

    /**
     * @var resource[]
     */
    private array $rdStreams = [];

    /**
     * @var callable[]
     */
    private array $rdHandlers = [];

    /**
     * @var resource[]
     */
    private array $wrStreams = [];

    /**
     * @var callable[]
     */
    private array $wrHandlers = [];

    public function __construct(int $timeoutMs = null)
    {
        $this->timeoutMs = $timeoutMs ?: self::DEFAULT_TIMEOUT_MS;
    }

    public function attachStreamRD($stream, callable $listener): void
    {
        $key = (int) $stream;
        $this->rdStreams[$key] = $stream;
        $this->rdHandlers[$key] = $listener;
    }

    public function detachStreamRD($stream): void
    {
        $key = (int) $stream;
        unset($this->rdStreams[$key], $this->rdHandlers[$key]);
    }

    public function attachStreamWR($stream, callable $listener): void
    {
        $key = (int) $stream;
        $this->wrStreams[$key] = $stream;
        $this->wrHandlers[$key] = $listener;
    }

    public function detachStreamWR($stream): void
    {
        $key = (int) $stream;
        unset($this->wrStreams[$key], $this->wrHandlers[$key]);
    }

    public function dispatch(int $timeoutMs = null): int
    {
        $timeout = $timeoutMs ?: $this->timeoutMs;

        // Cleanup dead streams
        foreach ($this->rdStreams as $stream) {
            if (!is_resource($stream)) {
                $this->detachStreamRD($stream);
            }
        }
        foreach ($this->wrStreams as $stream) {
            if (!is_resource($stream)) {
                $this->detachStreamWR($stream);
            }
        }

        // Extract resource pointers
        $rd = $this->rdStreams;
        $wr = $this->wrStreams;

        if ($rd || $wr) {
            // @codeCoverageIgnoreStart
            $previous = set_error_handler(function ($errno, $error) use (&$previous) {
                // suppress warnings that occur when `stream_select()` is interrupted by a signal
                if (E_WARNING === $errno && false !== strpos($error, '[' . SOCKET_EINTR .']: ')) {
                    return true;
                }

                // forward any other error to registered error handler or print warning
                return null !== $previous
                    ? call_user_func_array($previous, func_get_args())
                    : false;
            });
            // @codeCoverageIgnoreEnd

            try {
                $ex  = [];
                $num = stream_select($rd, $wr, $ex, null === $timeout ? null : 0, $timeout);
                restore_error_handler();
            } catch (\Throwable $e) {
                restore_error_handler();
                throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
            }

            if ($num) {
                foreach ($rd as $resource) {
                    if (isset($this->rdHandlers[(int) $resource])) {
                        call_user_func($this->rdHandlers[(int) $resource], $resource, $this);
                    }
                }
                foreach ($wr as $resource) {
                    if (isset($this->wrHandlers[(int) $resource])) {
                        call_user_func($this->wrHandlers[(int) $resource], $resource, $this);
                    }
                }
            }
            return $num;
        }
        return 0;
    }
}
