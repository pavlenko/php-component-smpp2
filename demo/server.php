<?php

namespace PE\SMPP;

use PE\Component\Loop\Loop;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));
$server = new Server('127.0.0.1:2775', $logger);
$server->init();

$loop = new Loop(10);
$loop->addPeriodicTimer(0.5, fn() => $server->tick());
$loop->addSingularTimer(10, function (Loop $loop) use ($server) {
    $loop->stop();
    $server->stop();
});
$loop->run();
