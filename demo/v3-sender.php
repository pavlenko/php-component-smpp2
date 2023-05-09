<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\FactoryOld;
use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\Sender;
use PE\Component\SMPP\Session;
use PE\Component\SMPP\DTO\SMS;
use PE\Component\SMPP\Util\Events;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$factory = new FactoryOld();
$session = new Session('SENDER');
$events  = new Events();
$logger  = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));

$sender = new Sender('127.0.0.1:2775', $factory, $session, $events, $logger);
$sender->bind();
$sender->sendSMS(new SMS('HELLO', new Address(0, 0, '+38(066)0660660')));
$sender->exit();
