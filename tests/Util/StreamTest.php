<?php

namespace PE\SMPP\Tests\Util;

use PE\SMPP\Util\Stream;
use PE\SMPP\Util\StreamException;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;

final class StreamTest extends TestCase
{
    use PHPMock;

    /**
     * @return resource
     */
    private function getResource()
    {
        return fopen('php://temp', 'w+');
    }

    /**
     * @runInSeparateProcess
     */
    public function testConstructFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'get_resource_type');
        $f->expects(self::once())->willReturn('foo');

        new Stream(curl_init());
    }

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
        $f->expects(self::once())->willReturn($this->getResource());

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
        $f->expects(self::once())->willReturn($this->getResource());

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
        $f->expects(self::once())->willReturn([$this->getResource(), $this->getResource()]);

        $pair = Stream::createPair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        self::assertInstanceOf(Stream::class, $pair[0]);
        self::assertInstanceOf(Stream::class, $pair[1]);
    }

    public function testSelectFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_select');
        $f->expects(self::once())->willReturn(false);

        $r = [];
        $w = [];
        $e = [];

        Stream::select($r, $w, $e, 1);
    }

    public function testSelectSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_select');
        $f->expects(self::once())->willReturnCallback(function (&$r) {
            unset($r[0]);
            return 1;
        });

        $s1 = new Stream($this->getResource());
        $s2 = new Stream($this->getResource());

        $r = [$s1, $s2];
        $w = [];
        $e = [];

        Stream::select($r, $w, $e, 1);

        self::assertCount(1, $r);
        self::assertSame($s2, current($r));
    }

    public function testSetTimeoutFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_timeout');
        $f->expects(self::once())->willReturn(false);

        (new Stream($this->getResource()))->setTimeout(1);
    }

    public function testSetTimeoutSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_timeout');
        $f->expects(self::once())->willReturn(true);

        (new Stream($this->getResource()))->setTimeout(1);
    }

    public function testSetBlockingFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_blocking');
        $f->expects(self::once())->willReturn(false);

        (new Stream($this->getResource()))->setBlocking(true);
    }

    public function testSetBlockingSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_blocking');
        $f->expects(self::once())->willReturn(true);

        (new Stream($this->getResource()))->setBlocking(true);
    }

    public function testSetBufferRFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_read_buffer');
        $f->expects(self::once())->willReturn(1);

        (new Stream($this->getResource()))->setBufferR(1);
    }

    public function testSetBufferRSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_read_buffer');
        $f->expects(self::once())->willReturn(0);

        (new Stream($this->getResource()))->setBufferR(1);
    }

    public function testSetBufferWFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_write_buffer');
        $f->expects(self::once())->willReturn(1);

        (new Stream($this->getResource()))->setBufferW(1);
    }

    public function testSetBufferWSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_set_write_buffer');
        $f->expects(self::once())->willReturn(0);

        (new Stream($this->getResource()))->setBufferW(1);
    }

    public function testSetCryptoFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_enable_crypto');
        $f->expects(self::once())->willReturn(false);

        (new Stream($this->getResource()))->setCrypto(true);
    }

    public function testSetCryptoSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_enable_crypto');
        $f->expects(self::once())->willReturn(true);

        (new Stream($this->getResource()))->setCrypto(true);
    }

    public function testSetOptionsFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_context_set_option');
        $f->expects(self::once())->willReturn(false);

        (new Stream($this->getResource()))->setOptions([]);
    }

    public function testSetOptionsSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_context_set_option');
        $f->expects(self::once())->willReturn(true);

        (new Stream($this->getResource()))->setOptions([]);
    }

    public function testGetOptions(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_context_get_options');
        $f->expects(self::once())->willReturn([]);

        (new Stream($this->getResource()))->getOptions();
    }

    public function testGetMetadata(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_get_meta_data');
        $f->expects(self::once())->willReturn([]);

        (new Stream($this->getResource()))->getMetadata();
    }

    public function testAcceptFailure(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_accept');
        $f->expects(self::once())->willReturn(false);

        self::assertNull((new Stream($this->getResource()))->accept());
    }

    public function testAcceptSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_socket_accept');
        $f->expects(self::once())->willReturn($this->getResource());

        self::assertInstanceOf(Stream::class, (new Stream($this->getResource()))->accept());
    }

    public function testCopyToFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_copy_to_stream');
        $f->expects(self::once())->willReturn(false);

        (new Stream($this->getResource()))->copyTo(new Stream($this->getResource()));
    }

    public function testCopyToSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'stream_copy_to_stream');
        $f->expects(self::once())->willReturn(1);

        (new Stream($this->getResource()))->copyTo(new Stream($this->getResource()), 1);
    }

    public function testReadLineFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'fgets');
        $f->expects(self::once())->willReturn(false);

        (new Stream($this->getResource()))->readLine();
    }

    public function testReadLineSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'fgets');
        $f->expects(self::once())->willReturn(1);

        (new Stream($this->getResource()))->readLine();
    }

    public function testReadDataFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'fread');
        $f->expects(self::once())->willReturn(false);

        (new Stream($this->getResource()))->readData();
    }

    public function testReadDataSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'fread');
        $f->expects(self::once())->willReturn(1);

        (new Stream($this->getResource()))->readData();
    }

    public function testSendDataFailure(): void
    {
        $this->expectException(StreamException::class);

        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'fwrite');
        $f->expects(self::once())->willReturn(false);

        (new Stream($this->getResource()))->sendData('D');
    }

    public function testSendDataSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);
        $f = $this->getFunctionMock($r->getNamespaceName(), 'fwrite');
        $f->expects(self::once())->willReturn(1);

        (new Stream($this->getResource()))->sendData('D');
    }

    public function testCloseSkipped(): void
    {
        $r = new \ReflectionClass(Stream::class);

        $f1 = $this->getFunctionMock($r->getNamespaceName(), 'is_resource');
        $f1->expects(self::once())->willReturn(false);

        $f2 = $this->getFunctionMock($r->getNamespaceName(), 'fclose');
        $f2->expects(self::never());

        (new Stream($this->getResource()))->close();
    }

    public function testCloseSuccess(): void
    {
        $r = new \ReflectionClass(Stream::class);

        $f1 = $this->getFunctionMock($r->getNamespaceName(), 'is_resource');
        $f1->expects(self::once())->willReturn(true);

        $f2 = $this->getFunctionMock($r->getNamespaceName(), 'fclose');
        $f2->expects(self::once());

        (new Stream($this->getResource()))->close();
    }
}
