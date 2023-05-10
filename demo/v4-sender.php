<?php

namespace PE\Component\SMPP\V4;

use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\SMS;
use PE\Component\SMPP\Factory4;
use PE\Component\SMPP\Sender4;
use PE\Component\SMPP\Session;
use PE\Component\Socket\Factory;
use PE\Component\Socket\Select;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

//TODO transmitter can send submit_sm and query_sm and unbind
$sender = new Sender4(
    new Session('ID', null, new Address(Address::TON_INTERNATIONAL, Address::NPI_ISDN, '10001112233')),
    new Factory4($select = new Select(), new Factory($select)),
    new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG))
);
$sender->bind('127.0.0.1:2775');
$sender->send(new SMS('HELLO', new Address(Address::TON_INTERNATIONAL, Address::NPI_ISDN, '10001112244')));
$sender->wait();
$sender->exit();
