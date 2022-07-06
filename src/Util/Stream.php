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
     * @param float|null $timeout Connection timeout
     * @param resource   $context Stream transport related context
     *
     * @return static
     */
    public static function createClient(string $address, $context = null, ?float $timeout = null): self
    {
        $socket = @stream_socket_client(
            $address,
            $errorNum,
            $errorStr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (false === $socket) {
            throw new SocketException($errorStr ?: 'Cannot connect to socket ' . $address, $errorNum);
        }
        return new self($socket);
    }

    /**
     * Create server socket
     *
     * @param string     $address Address to the socket to listen to.
     * @param resource   $context Stream transport related context
     *
     * @return static
     */
    public static function createServer(string $address, $context = null): self
    {
        $socket = @stream_socket_server(
            $address,
            $errorNum,
            $errorStr,
            STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
            $context
        );
        if (false === $socket) {
            throw new SocketException($errorStr ?: 'Cannot connect to socket ' . $address, $errorNum);
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
            throw new SocketException('Cannot create socket pair');
        }
        return [new self($sockets[0]), new self($sockets[1])];
    }

//stream_context_create — Создаёт контекст потока
//stream_context_get_default — Получает контекст потока по умолчанию
//stream_context_get_options — Получает опции для потока/обёртки/контекста
//stream_context_get_params — Получает параметры из контекста
//stream_context_set_default — Установить контекст потока по умолчанию
//stream_context_set_option — Устанавливает опцию для потока/обёртки/контекста
//stream_context_set_params — Устанавливает параметры для потока/обёртки/контекста
//stream_copy_to_stream — Копирует данные из одного потока в другой
//stream_get_meta_data — Извлекает заголовок/метаданные из потоков/файловых указателей
//stream_set_chunk_size — Установить размер фрагмента данных потока
//stream_set_read_buffer — Установить буферизацию чтения файла на указанном потоке
//stream_set_write_buffer — Устанавливает буферизацию файла при записи в указанный поток
//stream_socket_accept — Принимать соединение в сокете, созданном c помощью функции stream_socket_server
//stream_socket_enable_crypto — Включает или отключает шифрование на уже подключённом сокете
//stream_socket_get_name — Получить название локального или удалённого сокета

    /**
     * Create stream with specified resource, private for prevent call outside
     *
     * @param resource $resource
     */
    private function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Set read/write timeout
     *
     * @param int $seconds
     * @param int $micros
     */
    public function setTimeout(int $seconds, int $micros = 0): void
    {
        if (!stream_set_timeout($this->resource, $seconds, $micros)) {
            throw new SocketException('Cannot set read/write timeout');
        }
    }

    /**
     * Set blocking/non-blocking mode
     *
     * @param bool $enable
     */
    public function setBlocking(bool $enable): void
    {
        if (!stream_set_blocking($this->resource, $enable)) {
            throw new SocketException('Cannot set blocking mode');
        }
    }

    /**
     * Check if stream ready to read
     *
     * @param float|null $timeout
     *
     * @return bool
     */
    public function selectR(float $timeout = null): bool
    {
        $us = null !== $timeout ? ($timeout - floor($timeout)) * 1_000_000 : 0;

        $r = [$this->resource];
        $e = null;
        $n = stream_select($r, $e, $e, null !== $timeout ? (int) $timeout : null, $us);
        if (false === $n) {
            throw new SocketException('Cannot set blocking mode');
        }
        return (bool) $n;
    }

    /**
     * Check if stream ready to write
     *
     * @param float|null $timeout
     *
     * @return bool
     */
    public function selectW(float $timeout = null): bool
    {
        $us = null !== $timeout ? ($timeout - floor($timeout)) * 1_000_000 : 0;

        $w = [$this->resource];
        $e = null;
        $n = stream_select($e, $w, $e, null !== $timeout ? (int) $timeout : null, $us);
        if (false === $n) {
            throw new SocketException('Cannot set blocking mode');
        }
        return (bool) $n;
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
        $string = fgets($this->resource, $length);
        if (false === $string) {
            throw new SocketException('Cannot read line from socket');
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
        $string = fread($this->resource, $length ?: PHP_INT_MAX);
        if (false === $string) {
            throw new SocketException('Cannot read data from socket');
        }
        return $string;
    }

    /**
     * Send data to stream (can be truncated if length greater than $length)
     *
     * @param string   $data
     * @param int|null $length
     */
    public function sendData(string $data, int $length = null): void
    {
        if (false === fwrite($this->resource, $data, $length)) {
            throw new SocketException('Cannot write data to socket');
        }
    }

    /**
     * Close stream
     */
    public function close(): void
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }
}
