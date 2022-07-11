<?php

namespace PE\SMPP;

use PE\Component\Loop\Loop;
use PE\SMPP\Util\Stream;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));
$server = Stream::createServer('127.0.0.1:2775');
$client = null;

$loop = new Loop(10);
$loop->addSingularTimer(30, fn(Loop $loop) => $loop->stop());
$loop->addPeriodicTimer(0.01, function () use ($server, &$client, $logger) {
    $r = [$server];
    $n = [];
    Stream::select($r, $n, $n, 0.05);

    if (!empty($r)) {
        $logger->log(LogLevel::DEBUG, 'ACCEPT');
        $client = $server->accept();
    }
});
$t = $loop->addPeriodicTimer(5, function (Loop $loop) use ($server, &$client, $logger, &$t) {
    if (!$client) {
        return;
    }
    $logger->log(LogLevel::DEBUG, 'SEND');

    $num = $client->sendData('TEST');
    $res = $client->readData(1024);
    if ($num === 0 || '' === $res) {
        $logger->log(LogLevel::DEBUG, 'CLOSE CLIENT');
        $loop->removeTimer($t);
        return;
    }
    $logger->log(LogLevel::DEBUG, 'RESP: ' . $res);
});
$loop->run();
