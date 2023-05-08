<?php

namespace PE\Component\SMPP\V4;

use PE\Component\Event\Emitter;
use PE\Component\SMPP\Server4;
use PE\Component\SMPP\Util\Serializer;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

//$select  = new Select();
//$factory = new Factory($select);
//$loop    = new Loop(1, fn() => $select->dispatch());
//
//$server = $factory->createServer('127.0.0.1:2775');
//$server->setInputHandler(function (ClientInterface $socket) use ($loop) {
//    $client = $socket;
//    $client->setInputHandler(function (string $data) {
//        echo trim($data) . "\n";
//    });
//    $client->write("WELCOME\n");
//
//    echo "new connection from {$client->getRemoteAddress()}\n";
//
//    $loop->addSingularTimer(10, function () use (&$client) {
//        echo "disconnect client {$client->getRemoteAddress()}\n";
//        $client->close();
//    });
//});
//
//$server->setErrorHandler(function (\Throwable $throwable) {
//    echo $throwable . "\n";
//});
//
//$server->setCloseHandler(function (string $message) {
//    echo $message . "\n";
//});
//
//echo "listen to {$server->getAddress()}\n";
//$loop->run();

$emitter    = new Emitter();
$serializer = new Serializer();
$logger     = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));

$server = new Server4($emitter, $serializer, $logger);
$server->bind('127.0.0.1:2775');
