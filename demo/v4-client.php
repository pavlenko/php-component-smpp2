<?php

namespace PE\Component\SMPP\V4;

use PE\Component\Loop\Loop;
use PE\Component\Socket\Factory;
use PE\Component\Socket\Select;

require_once __DIR__ . '/../vendor/autoload.php';

$select  = new Select();
$factory = new Factory($select);
$loop    = new Loop(1, fn() => $select->dispatch());

$client = $factory->createClient('127.0.0.1:2775');
$client->setInputHandler(function (string $data) use ($client) {
    echo trim($data) . "\n";
    $client->write("HELLO\n");
});
$client->setErrorHandler(function (\Throwable $throwable) {
    echo $throwable . "\n";
});
$client->setCloseHandler(function (string $message) use ($loop) {
    echo $message . "\n";
    $loop->stop();
});

echo "connected to {$client->getRemoteAddress()}\n";
$loop->run();
