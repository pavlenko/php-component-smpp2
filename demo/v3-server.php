<?php

namespace PE\Component\SMPP\V3;

use PE\Component\Loop\Loop;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$factory = new Factory();
$session = new Session('SERVER');
$logger  = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));

$server = new Server('127.0.0.1:2775', $factory, $session, $logger);
$server->on(Server::EVENT_RECEIVE, fn($c, $p) => var_dump($p));
$server->bind();

$loop = new Loop();
$loop->addPeriodicTimer(0.01, fn() => $server->tick());
$loop->run();
