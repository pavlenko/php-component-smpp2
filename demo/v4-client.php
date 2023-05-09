<?php

namespace PE\Component\SMPP\V4;

use PE\Component\SMPP\Client4;
use PE\Component\SMPP\Session;
use PE\Component\SMPP\Util\Serializer;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$session = new Session('CLIENT');
$logger  = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));

$client = new Client4($session, new Serializer(), $logger);
$client->bind('127.0.0.1:2775');
