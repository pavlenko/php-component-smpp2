<?php

namespace PE\Component\SMPP;

use PE\Component\Loop\Loop;
use PE\Component\Loop\LoopInterface;
use PE\Component\SMPP\Util\Decoder;
use PE\Component\SMPP\Util\DecoderInterface;
use PE\Component\SMPP\Util\Encoder;
use PE\Component\SMPP\Util\EncoderInterface;
use PE\Component\SMPP\Util\Validator;
use PE\Component\SMPP\Util\ValidatorInterface;
use PE\Component\Socket\ClientInterface as SocketClientInterface;
use PE\Component\Socket\FactoryInterface as SocketFactoryInterface;
use PE\Component\Socket\SelectInterface as SocketSelectInterface;
use PE\Component\Socket\ServerInterface as SocketServerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Factory implements FactoryInterface
{
    private SocketSelectInterface $socketSelect;
    private SocketFactoryInterface $socketFactory;
    private DecoderInterface $decoder;
    private EncoderInterface $encoder;
    private ValidatorInterface $validator;
    private LoggerInterface $logger;

    public function __construct(
        SocketSelectInterface  $socketSelect,//TODO nullable
        SocketFactoryInterface $socketFactory,//TODO create factory without select, maybe default & getter
        DecoderInterface       $decoder = null,
        EncoderInterface       $encoder = null,
        ValidatorInterface     $validator = null,
        LoggerInterface        $logger = null
    ) {
        $this->socketSelect = $socketSelect;
        $this->socketFactory = $socketFactory;
        $this->decoder = $decoder ?: new Decoder();
        $this->encoder = $encoder ?: new Encoder();
        $this->validator = $validator ?: new Validator();
        $this->logger = $logger ?: new NullLogger();
    }

    public function createSocketClient(string $addr, array $ctx = [], float $timeout = null): SocketClientInterface
    {
        return $this->socketFactory->createClient($addr, $ctx, $timeout);
    }

    public function createSocketServer(string $addr, array $ctx = []): SocketServerInterface
    {
        return $this->socketFactory->createServer($addr, $ctx);
    }

    public function createDispatcher(callable $dispatch): LoopInterface
    {
        return new Loop(1, function () use ($dispatch) {
            $this->socketSelect->dispatch();
            call_user_func($dispatch);
        });
    }

    public function createConnection(SocketClientInterface $client): ConnectionInterface
    {
        return new Connection($client, $this->decoder, $this->encoder, $this->validator, $this->logger);
    }

    public function generateID(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,
            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
