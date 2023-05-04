<?php

namespace PE\Component\SMPP;

use PE\Component\Loop\Loop;
use PE\Component\Stream\Select;
use PE\Component\Stream\Socket;

class Server4
{
    public function __invoke(): void
    {}

    public function bind(string $address): void
    {
        $loop = new Loop();
        $factory = new \PE\Component\Stream\Factory();

        $stream = $factory->createServer($address);
        $stream->setBlocking(false);

        $select = new Select();
        $socket = new Socket($stream, $select);//TODO add input processor (for server and client)

        //TODO maybe split onInput into two, or first try to accept connection
        //TODO onConnect($stream)
        //TODO onReceive($stream)

        $socket->onInput(function ($data) use ($stream, $factory) {//<-- this is unusable for server socket
            var_dump($data);
        });
        $socket->onError(function ($error) {
            echo 'E: ' . $error . "\n";
        });
        $socket->onClose(function ($error) {
            echo 'C: ' . $error . "\n";
        });

        $loop->addPeriodicTimer(0.001, fn() => $select->dispatch());
        $loop->run();
    }

    public function stop(): void
    {}
}