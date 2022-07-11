<?php

use PE\Component\Loop\Loop;
use PE\SMPP\Client;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));
$client = new Client('127.0.0.1:2775', 'SYSTEM_ID', null, $logger);
$client->init();

$loop = new Loop(10);
$loop->addSingularTimer(30, fn(Loop $loop) => $loop->stop());
$loop->addPeriodicTimer(0.1, function (Loop $loop) use ($client) {
    if (!$client->tick()) {
        $loop->stop();
    }
});
$loop->run();

$client->stop();
