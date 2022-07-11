<?php

namespace PE\SMPP;

use PE\Component\Loop\Loop;
use PE\SMPP\Util\Stream;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));
$client = Stream::createClient('127.0.0.1:2775', null, Session::TIMEOUT_CONNECT);
$client->setBlocking(false);

$loop = new Loop(10);
$loop->addSingularTimer(30, fn(Loop $loop) => $loop->stop());
$loop->addPeriodicTimer(0.05, function (Loop $loop) use ($client, $logger) {
    $r = [$client];
    $n = [];
    Stream::select($r, $n, $n, 0.01);

    if (!empty($r)) {
        $logger->log(LogLevel::DEBUG, 'READ: ' . $client->readData(1024));
        if ($client->isEOF()) {
            $loop->stop();
            return;
        }
        $client->sendData('TEST');
        //stream_socket_sendto($client->getResource(), 'TEST');
    }
});
$loop->run();
