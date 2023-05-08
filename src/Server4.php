<?php

namespace PE\Component\SMPP;

use PE\Component\Loop\Loop;
use PE\Component\Socket\Factory as SocketFactory;
use PE\Component\Socket\Select;

final class Server4
{
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

        $server->setInputHandler(function ($data) {
            var_dump($data);
        });

        $server->setErrorHandler(function ($error) {
            echo 'E: ' . $error . "\n";
        });

        $server->setCloseHandler(function ($error = null) {
            echo 'C: ' . ($error ?: 'Closed') . "\n";
        });

        $loop->addPeriodicTimer(0.001, fn() => $select->dispatch());
        $loop->run();
    }

    public function stop(): void
    {
    }
}
