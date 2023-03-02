<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Factory;
use PE\Component\SMPP\PDU\Address;
use PE\Component\SMPP\SMS;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$factory = new Factory();
$session = new Session('SENDER');
$logger  = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));

$sender = new Sender('127.0.0.1:2775', $factory, $session, $logger);
$sender->bind();
$sender->sendSMS(new SMS('HELLO', new Address(0, 0, '+38(066)0660660')));
sleep(15);
$sender->exit();
