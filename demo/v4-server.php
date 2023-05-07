<?php

namespace PE\Component\SMPP\V4;

use PE\Component\Loop\Loop;
use PE\Component\SMPP\Socket\ClientInterface;
use PE\Component\SMPP\Socket\Factory;
use PE\Component\SMPP\Socket\Select;

require_once __DIR__ . '/../vendor/autoload.php';

$select  = new Select();
$factory = new Factory($select);
$loop    = new Loop(1, fn() => $select->dispatch());

$client = null;
$server = $factory->createServer('127.0.0.1:2775');
$server->setInputHandler(function (ClientInterface $socket) use (&$client) {
    $client = $socket;
    $client->setInputHandler(function (string $data) {
        echo "$data\n";
    });
    $client->write("WELCOME\n");
    echo "new connection from {$client->getClientAddress()}\n";
});
$server->setErrorHandler(function (\Throwable $throwable) {
    echo $throwable . "\n";
});
$server->setCloseHandler(function (string $message) {
    echo $message . "\n";
});

$loop->addSingularTimer(10, function () use ($client) {
    $client->close();
});
$loop->run();
