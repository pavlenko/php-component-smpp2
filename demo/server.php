<?php

namespace PE\SMPP;

use PE\Component\Loop\Loop;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));
$server = new ServerV2('127.0.0.1:2775', $logger);

$loop = new Loop(10);
$loop->addSingularTimer(15, fn(Loop $loop) => $loop->stop());

$server->run($loop);
