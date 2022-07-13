<?php

namespace PE\SMPP\Util;

final class Stream
{
    /**
     * @var resource
     */
    private $resource;

    /**
     * Create client socket
     *
     * @param string     $address Address to the socket to connect to.
     * @param array      $context Stream transport related context
     * @param float|null $timeout Connection timeout
     *
     * @return static
     */
    public static function createClient(string $address, array $context = [], ?float $timeout = null): self
    {
        $socket = stream_socket_client(
            $address,
            $errorNum,
            $errorStr,
            $timeout,
            STREAM_CLIENT_CONNECT|STREAM_CLIENT_ASYNC_CONNECT,
            stream_context_create($context)
        );
        if (false === $socket) {
            throw new StreamException($errorStr ?: 'Cannot connect to socket ' . $address, $errorNum);
        }
        return new self($socket);
    }

    /**
     * Create server socket
     *
     * @param string $address Address to the socket to listen to.
     * @param array  $context Stream transport related context
     *
     * @return static
     */
    public static function createServer(string $address, array $context = []): self
    {
        $socket = stream_socket_server(
            $address,
            $errorNum,
            $errorStr,
            STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
            stream_context_create($context)
        );
        if (false === $socket) {
            throw new StreamException($errorStr ?: 'Cannot connect to socket ' . $address, $errorNum);
        }
        return new self($socket);
    }

    /**
     * Create socket pair
     *
     * @param int $domain The protocol family to be used:<br>
     *   <b>STREAM_PF_INET</b>,<br>
     *   <b>STREAM_PF_INET6</b> or<br>
     *   <b>STREAM_PF_UNIX</b>
     * @param int $type The type of communication to be used:<br>
     *   <b>STREAM_SOCK_DGRAM</b>,<br>
     *   <b>STREAM_SOCK_RAW</b>,<br>
     *   <b>STREAM_SOCK_RDM</b>,<br>
     *   <b>STREAM_SOCK_SEQPACKET</b> or<br>
     *   <b>STREAM_SOCK_STREAM</b>
     * @param int $protocol The protocol to be used:<br>
     *   <b>STREAM_IPPROTO_ICMP</b>,<br>
     *   <b>STREAM_IPPROTO_IP</b>,<br>
     *   <b>STREAM_IPPROTO_RAW</b>,<br>
     *   <b>STREAM_IPPROTO_TCP</b> or<br>
     *   <b>STREAM_IPPROTO_UDP</b>
     *
     * @return Stream[]
     */
    public static function createPair(int $domain, int $type, int $protocol): array
    {
        $sockets = stream_socket_pair($domain, $type, $protocol);
        if (false === $sockets) {
            throw new StreamException('Cannot create socket pair');
        }
        return [new self($sockets[0]), new self($sockets[1])];
    }

    /**
     * Call system select() for resolve streams which is ready to read/write
     *
     * @param static[] $rStreams
     * @param static[] $wStreams
     * @param static[] $eStreams
     * @param float|null $timeout
     *
     * @return int
     */
    public static function select(array &$rStreams, array &$wStreams, array &$eStreams, float $timeout = null): int
    {
        $us = null !== $timeout
            ? ($timeout - floor($timeout)) * 1_000_000
            : 0;

        $rResources = array_map(fn(Stream $stream) => $stream->resource, $rStreams);
        $wResources = array_map(fn(Stream $stream) => $stream->resource, $wStreams);
        $eResources = array_map(fn(Stream $stream) => $stream->resource, $eStreams);

        $num = @stream_select(
            $rResources,
            $wResources,
            $eResources,
            null !== $timeout ? (int) $timeout : null,
            $us
        );

        if (false === $num) {
            throw new StreamException('Cannot select');
        }

        $rStreams = array_filter($rStreams, fn(Stream $stream) => in_array($stream->resource, $rResources));
        $wStreams = array_filter($wStreams, fn(Stream $stream) => in_array($stream->resource, $wResources));
        $eStreams = array_filter($eStreams, fn(Stream $stream) => in_array($stream->resource, $eResources));

        return $num;
    }

    /**
     * Create stream with specified resource
     *
     * @param resource $resource
     */
    public function __construct($resource)
    {
        if (!is_resource($resource) || get_resource_type($resource) !== 'stream') {
            throw new StreamException('First parameter must be a valid stream resource');
        }
        $this->resource = $resource;
    }

    /**
     * @return resource
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Set read/write timeout
     *
     * @param int $seconds
     * @param int $micros
     *
     * @return Stream
     */
    public function setTimeout(int $seconds, int $micros = 0): self
    {
        if (!stream_set_timeout($this->resource, $seconds, $micros)) {
            throw new StreamException('Cannot set read/write timeout');
        }
        return $this;
    }

    /**
     * Set blocking/non-blocking mode
     *
     * @param bool $enable
     *
     * @return Stream
     */
    public function setBlocking(bool $enable): self
    {
        if (!stream_set_blocking($this->resource, $enable)) {
            throw new StreamException('Cannot set blocking mode');
        }
        return $this;
    }

    /**
     * Set the read buffer
     *
     * @param int $size The number of bytes to buffer. If <b>$size</b> is 0 then operations are unbuffered
     */
    public function setBufferR(int $size): void
    {
        if (0 !== stream_set_read_buffer($this->resource, $size)) {
            throw new StreamException('Cannot set read buffer');
        }
    }

    /**
     * Set the write buffer
     *
     * @param int $size The number of bytes to buffer. If <b>$size</b> is 0 then operations are unbuffered
     */
    public function setBufferW(int $size): void
    {
        if (0 !== stream_set_write_buffer($this->resource, $size)) {
            throw new StreamException('Cannot set write buffer');
        }
    }

    /**
     * Turns encryption on/off
     *
     * @param bool     $enabled
     * @param int|null $method
     */
    public function setCrypto(bool $enabled, int $method = null): void
    {
        if (!stream_socket_enable_crypto($this->resource, $enabled, $method)) {
            throw new StreamException('Cannot set crypto method(s)');
        }
    }

    /**
     * Retrieve the name of the remote socket
     *
     * @return string
     */
    public function getPeerName(): string
    {
        $name = stream_socket_get_name($this->resource, true);
        if (false === $name) {
            throw new StreamException('Cannot retrieve the name of socket');
        }
        return $name;
    }

    /**
     * Retrieves header/metadata
     *
     * @return array
     */
    public function getMetadata(): array
    {
        return stream_get_meta_data($this->resource);
    }

    /**
     * Get options in format:
     * <code>
     * $options = [
     *     'wrapper_name' => ['option_name' => $value, ...],
     *     ...
     * ]
     * </code>
     *
     * @return array
     */
    public function getOptions(): array
    {
        return stream_context_get_options($this->resource);
    }

    /**
     * Set options in format:
     * <code>
     * $options = [
     *     'wrapper_name' => ['option_name' => $value, ...],
     *     ...
     * ]
     * </code>
     *
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        if (!stream_context_set_option($this->resource, $options)) {
            throw new StreamException('Cannot set options');
        }
    }

    public function isEOF(): bool
    {
        return @feof($this->resource);
    }

    /**
     * Accept a connection on a socket
     *
     * @param float|null $timeout
     *
     * @return static|null
     */
    public function accept(float $timeout = 0): ?self
    {
        $socket = stream_socket_accept($this->resource, $timeout);
        return $socket ? new self($socket) : null;
    }

    /**
     * Copy stream data to another one
     *
     * @param Stream $stream
     * @param int    $length
     * @param int    $offset
     *
     * @return int
     */
    public function copyTo(self $stream, int $length = 0, int $offset = 0): int
    {
        $pos = 0;
        while (!feof($this->resource) && (0 === $length || $pos < $length)) {
            $num = stream_copy_to_stream($this->resource, $stream->resource, 8192, $offset + $pos);
            if (false === $num) {
                throw new StreamException('Cannot copy stream data');
            }
            $pos += $num;
        }
        return $pos;
    }

    /**
     * Read line from stream until reach $length or EOL or EOF
     *
     * @param int|null $length
     *
     * @return string
     */
    public function readLine(int $length = null): string
    {
        $string = @fgets($this->resource, $length);
        if (false === $string) {
            throw new StreamException('Cannot read line from stream');
        }
        return $string;
    }

    /**
     * Read data from stream until reach $limit or EOL
     *
     * @param int|null $length
     *
     * @return string
     */
    public function readData(int $length = null): string
    {
        $string = @fread($this->resource, $length ?: PHP_INT_MAX);
        if (false === $string) {
            throw new StreamException('Cannot read data from stream');
        }
        return $string;
    }

    /**
     * Send data to stream (can be truncated if length greater than $length)
     *
     * @param string   $data
     * @param int|null $length
     *
     * @return int
     */
    public function sendData(string $data, int $length = null): int
    {
        $num = @fwrite($this->resource, $data, $length ?: strlen($data));
        if (false === $num) {
            throw new StreamException('Cannot send data to stream');
        }
        return $num;
    }

    /**
     * Close stream
     */
    public function close(): self
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
        return $this;
    }
}
