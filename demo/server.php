<?php

namespace PE\SMPP;

use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));
$server = new Server('127.0.0.1:2775', $logger);
$server->init();

$tick = microtime(true);
$time = microtime(true);
while (microtime(true) - $tick < 15) {
    if (microtime(true) - $time > 2) {
        $time = microtime(true);
        $server->tick();
    }
    usleep(100_000);
    echo "tick\n";
}
$server->stop();
