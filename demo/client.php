<?php

namespace PE\SMPP;

use PE\SMPP\PDU\Address;
use PE\SMPP\Util\Stream;
use Psr\Log\LogLevel;
use React\EventLoop\Loop;
use React\EventLoop\StreamSelectLoop;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$logger = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));

$stream = Stream::createClient('127.0.0.1:2775', null, Session::TIMEOUT_CONNECT)->getResource();

$loop = new StreamSelectLoop();
$loop->addPeriodicTimer(0.5, fn() => $logger->log(LogLevel::DEBUG, 'TICK'));
$loop->addReadStream($stream, function () {
    var_dump(func_get_args());
});
$loop->addWriteStream($stream, function () {
    var_dump(func_get_args());
});
$loop->run();
return;
//$client = new Client2('127.0.0.1:2775', 'user', 'pass', $logger);
//$client->init();
//$client->send(
//    new Address(Address::TON_INTERNATIONAL, Address::NPI_ISDN, '1234567890'),
//    new Address(Address::TON_INTERNATIONAL, Address::NPI_ISDN, '1234567890'),
//    'TEST'
//);

$loop = new Loop(10);
$loop->addPeriodicTimer(0.5, fn() => $client->tick());
$loop->addSingularTimer(60, function (Loop $loop) use ($client) {
    $loop->stop();
    //$client->stop();
});
$loop->run();
