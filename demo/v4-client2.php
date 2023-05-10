<?php

namespace PE\Component\SMPP\V4;

use PE\Component\Event\Emitter;
use PE\Component\SMPP\Client4;
use PE\Component\SMPP\Connection4;
use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Factory4;
use PE\Component\SMPP\Session;
use PE\Component\SMPP\Storage4;
use PE\Component\Socket\Factory;
use PE\Component\Socket\Select;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$client = new Client4(
    new Session('ID', null, new Address(Address::TON_INTERNATIONAL, Address::NPI_ISDN, '10001112233')),
    new Storage4(),
    $emitter = new Emitter(),
    new Factory4($select = new Select(), new Factory($select)),
    new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG))
);
$emitter->attach('bound', function (Connection4 $connection4) {
    $client->submit_sm()//disallow in tx mode
        ->then(/*success*/)
        ->else(/*failure*/);
});
$client->bind('127.0.0.1:2775', PDU::ID_BIND_TRANSMITTER)
    ->then()
    ->else()
;
