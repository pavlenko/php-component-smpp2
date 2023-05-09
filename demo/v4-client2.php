<?php

namespace PE\Component\SMPP\V4;

use PE\Component\SMPP\Client4;
use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\Session;
use PE\Component\SMPP\Util\Serializer;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$client = new Client4(
    new Session('ID', null, new Address(Address::TON_INTERNATIONAL, Address::NPI_ISDN, '10001112233')),
    new Serializer(),
    new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG))
);
$client->bind('127.0.0.1:2775');
