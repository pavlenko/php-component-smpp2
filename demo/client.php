<?php

namespace PE\Component\SMPP\V4;

use PE\Component\Event\Emitter;
use PE\Component\SMPP\Client;
use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Factory;
use PE\Component\SMPP\Session;
use PE\Component\SMPP\StorageMemory;
use PE\Component\Socket\Factory as SocketFactory;
use PE\Component\Socket\Select;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set('Europe/Kiev');

$logger = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));
$client = new Client(
    new Session('ID', null, new Address(Address::TON_INTERNATIONAL, Address::NPI_ISDN, '10001112244')),
    new StorageMemory(),
    new Emitter(),
    new Factory($select = new Select(), new SocketFactory($select), null, null, $logger),
    $logger
);
$client->bind('127.0.0.1:2775', PDU::ID_BIND_RECEIVER);
$client->wait();
