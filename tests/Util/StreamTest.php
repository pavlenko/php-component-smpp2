<?php

namespace PE\SMPP\Tests\Util;

use PE\SMPP\Util\Stream;
use PE\SMPP\Util\StreamException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertInstanceOf;

final class StreamTest extends TestCase
{
    use PHPMock;

    public function testCreateClientFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_client');
        $f->expects(self::once())->willReturn(false);

        Stream::createClient('127.0.0.1');
    }

    public function testCreateClientSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_client');
        $f->expects(self::once())->willReturn(STDOUT);

        self::assertInstanceOf(Stream::class, Stream::createClient('127.0.0.1'));
    }

    public function testCreateServerFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_server');
        $f->expects(self::once())->willReturn(false);

        Stream::createServer('127.0.0.1');
    }

    public function testCreateServerSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_server');
        $f->expects(self::once())->willReturn(STDOUT);

        self::assertInstanceOf(Stream::class, Stream::createServer('127.0.0.1'));
    }

    public function testCreatePairFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_pair');
        $f->expects(self::once())->willReturn(false);

        Stream::createPair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    }

    public function testCreatePairSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_pair');
        $f->expects(self::once())->willReturn([STDOUT, STDOUT]);

        $pair = Stream::createPair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        self::assertInstanceOf(Stream::class, $pair[0]);
        self::assertInstanceOf(Stream::class, $pair[1]);
    }

    public function testSetTimeoutFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_timeout');
        $f->expects(self::once())->willReturn(false);

        (new Stream(STDOUT))->setTimeout(1);
    }

    public function testSetTimeoutSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_timeout');
        $f->expects(self::once())->willReturn(true);

        (new Stream(STDOUT))->setTimeout(1);
    }

    public function testSetBlockingFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_blocking');
        $f->expects(self::once())->willReturn(false);

        (new Stream(STDOUT))->setBlocking(true);
    }

    public function testSetBlockingSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_blocking');
        $f->expects(self::once())->willReturn(true);

        (new Stream(STDOUT))->setBlocking(true);
    }

    public function testSetBufferRFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_read_buffer');
        $f->expects(self::once())->willReturn(1);

        (new Stream(STDOUT))->setBufferR(1);
    }

    public function testSetBufferRSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_read_buffer');
        $f->expects(self::once())->willReturn(0);

        (new Stream(STDOUT))->setBufferR(1);
    }

    public function testSetBufferWFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_write_buffer');
        $f->expects(self::once())->willReturn(1);

        (new Stream(STDOUT))->setBufferW(1);
    }

    public function testSetBufferWSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_write_buffer');
        $f->expects(self::once())->willReturn(0);

        (new Stream(STDOUT))->setBufferW(1);
    }

    public function testSetCryptoFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_enable_crypto');
        $f->expects(self::once())->willReturn(false);

        (new Stream(STDOUT))->setCrypto(true);
    }

    public function testSetCryptoSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_enable_crypto');
        $f->expects(self::once())->willReturn(true);

        (new Stream(STDOUT))->setCrypto(true);
    }

    public function testSetOptionsFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_context_set_option');
        $f->expects(self::once())->willReturn(false);

        (new Stream(STDOUT))->setOptions([]);
    }

    public function testSetOptionsSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_context_set_option');
        $f->expects(self::once())->willReturn(true);

        (new Stream(STDOUT))->setOptions([]);
    }

    public function testAcceptFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_accept');
        $f->expects(self::once())->willReturn(false);

        (new Stream(STDOUT))->accept();
    }

    public function testAcceptSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_accept');
        $f->expects(self::once())->willReturn(STDOUT);

        assertInstanceOf(Stream::class, (new Stream(STDOUT))->accept());
    }

    public function testSelectRFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_select');
        $f->expects(self::once())->willReturn(false);

        (new Stream(STDOUT))->selectR();
    }

    public function testSelectRSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_select');
        $f->expects(self::once())->willReturn(0);

        (new Stream(STDOUT))->selectR();
    }

    public function testSelectWFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_select');
        $f->expects(self::once())->willReturn(false);

        (new Stream(STDOUT))->selectW();
    }

    public function testSelectWSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_select');
        $f->expects(self::once())->willReturn(0);

        (new Stream(STDOUT))->selectW();
    }
}
