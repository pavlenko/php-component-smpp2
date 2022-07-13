<?php

namespace PE\SMPP;

use PE\Component\Loop\Loop;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new LoggerSTDOUT(LoggerSTDOUT::VERBOSITY_DEBUG);
$server = new Server('127.0.0.1:2775', $logger);
$server->init();

$loop = new Loop(10);
$loop->addSingularTimer(30, fn(Loop $loop) => $loop->stop());
$loop->addPeriodicTimer(0.1, fn(Loop $loop) => $server->tick());
$loop->run();

$server->stop();
