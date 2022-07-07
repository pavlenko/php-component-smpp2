<?php

namespace PE\SMPP\Tests\Util;

use PE\SMPP\Util\Stream;
use PE\SMPP\Util\StreamException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

final class StreamTest extends TestCase
{
    use PHPMock;

    public function testCreateClientFailure()
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_client');
        $f->expects(self::once())->willReturn(false);

        Stream::createClient('127.0.0.1');
    }

    public function testCreateClientSuccess()
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_client');
        $f->expects(self::once())->willReturn(STDOUT);

        self::assertInstanceOf(Stream::class, Stream::createClient('127.0.0.1'));
    }

    public function testCreateServerFailure()
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_server');
        $f->expects(self::once())->willReturn(false);

        Stream::createServer('127.0.0.1');
    }

    public function testCreateServerSuccess()
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_server');
        $f->expects(self::once())->willReturn(STDOUT);

        self::assertInstanceOf(Stream::class, Stream::createServer('127.0.0.1'));
    }
}
