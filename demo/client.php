<?php

namespace PE\SMPP;

use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));
$client = new Client('127.0.0.1:2775', 'user', 'pass', $logger);
$client->init();

//TODO allow call below
//$client->send(/*TODO src_addr, dst_addr, message*/)->wait();
sleep(2);
$client->stop();
