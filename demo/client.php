<?php

namespace PE\SMPP;

use PE\Component\Loop\Loop;
use PE\SMPP\PDU\Address;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));
$client = new Client('127.0.0.1:2775', 'user', 'pass', $logger);
$client->init();
$client->send(
    new Address(Address::TON_INTERNATIONAL, Address::NPI_ISDN, '1234567890'),
    new Address(Address::TON_INTERNATIONAL, Address::NPI_ISDN, '1234567890'),
    'TEST'
);

$loop = new Loop(10);
$loop->addPeriodicTimer(0.5, fn() => $client->tick());
$loop->addSingularTimer(60, function (Loop $loop) use ($client) {
    $loop->stop();
    $client->stop();
});
$loop->run();
