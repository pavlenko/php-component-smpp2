<?php

namespace PE\Component\SMPP;

use PE\Component\Event\Emitter;
use PE\Component\Event\EmitterInterface;
use PE\Component\Loop\Loop;
use PE\Component\SMPP\Util\Serializer;
use PE\Component\SMPP\Util\SerializerInterface;
use PE\Component\Socket\ClientInterface as SocketClientInterface;
use PE\Component\Socket\Factory as SocketFactory;
use PE\Component\Socket\Select;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

final class Server4
{
    private \SplObjectStorage $sessions;
    private EmitterInterface $emitter;
    private SerializerInterface $serializer;
    private LoggerInterface $logger;

    public function __construct(
        EmitterInterface $emitter,
        SerializerInterface $serializer,
        LoggerInterface $logger = null
    ) {
        $this->sessions   = new \SplObjectStorage();
        $this->emitter    = $emitter;
        $this->serializer = $serializer;
        $this->logger     = $logger ?: new NullLogger();
    }

    public function __invoke(): void
    {
    }

    public function bind(string $address): void
    {
        $loop    = new Loop();
        $select  = new Select();
        $factory = new SocketFactory($select);

        $server = $factory->createServer($address);

        //TODO maybe split onInput into two, or first try to accept connection
        //TODO onConnect($stream)
        //TODO onReceive($stream)

        $server->setInputHandler(function (SocketClientInterface $input) {
            $input->setErrorHandler(fn($error) => $this->logger->log(LogLevel::ERROR, '< E: ' . $error));

            //TODO detach session
            $input->setCloseHandler(fn($error) => $this->logger->log(LogLevel::DEBUG, '< C: ' . ($error ?: 'Closed')));

            $client = new Connection4($input, $this->emitter, $this->serializer, $this->logger);

            $this->logger->log(LogLevel::DEBUG, '> New connection from' . $input->getRemoteAddress());
            $this->sessions->attach($client);
        });

        $server->setErrorHandler(function ($error) {
            $this->logger->log(LogLevel::ERROR, 'E: ' . $error);
        });

        $server->setCloseHandler(function ($error = null) {
            $this->logger->log(LogLevel::DEBUG, 'C: ' . ($error ?: 'Closed'));
        });

        $this->logger->log(LogLevel::DEBUG, 'Listen to ' . $server->getAddress());

        $loop->addPeriodicTimer(0.001, fn() => $select->dispatch());
        $loop->run();
    }

    public function stop(): void
    {
    }
}
