<?php

namespace PE\Component\SMPP\V4;

use PE\Component\Event\Emitter;
use PE\Component\SMPP\Server4;
use PE\Component\SMPP\Util\Serializer;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$emitter    = new Emitter();
$serializer = new Serializer();
$logger     = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));

$server = new Server4($emitter, $serializer, $logger);
$server->bind('127.0.0.1:2775');
