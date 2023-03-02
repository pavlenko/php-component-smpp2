<?php

namespace PE\Component\SMPP\Tests;

use PE\Component\SMPP\ConnectionInterface;
use PE\Component\SMPP\Factory;
use PE\Component\SMPP\Util\Stream;
use PHPUnit\Framework\TestCase;

class FactoryTest extends TestCase
{
    public function testCreateStreamConnection(): void
    {
        $stream     = new Stream(fopen('php://temp', 'w+'));
        $connection = (new Factory())->createStreamConnection($stream);

        self::assertInstanceOf(ConnectionInterface::class, $connection);
        self::assertSame($stream, $connection->getStream());
    }

    public function testCreateClientConnection(): void
    {
        $connection = (new Factory())->createClientConnection('127.0.0.1:2775');
        self::assertInstanceOf(ConnectionInterface::class, $connection);
    }

    public function testCreateServerConnection(): void
    {
        $connection = (new Factory())->createServerConnection('127.0.0.1:2775');
        self::assertInstanceOf(ConnectionInterface::class, $connection);
    }
}