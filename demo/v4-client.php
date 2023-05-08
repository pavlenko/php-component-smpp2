<?php

namespace PE\Component\SMPP\V4;

use PE\Component\Event\Emitter;
use PE\Component\SMPP\Client4;
use PE\Component\SMPP\Session;
use PE\Component\SMPP\Util\Serializer;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$session    = new Session('CLIENT');
$emitter    = new Emitter();
$serializer = new Serializer();
$logger     = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));

$client = new Client4($session, $emitter, $serializer, $logger);
$client->bind('127.0.0.1:2775');
