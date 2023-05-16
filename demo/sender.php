<?php

namespace PE\Component\SMPP\V4;

use PE\Component\Event\Emitter;
use PE\Component\SMPP\Client;
use PE\Component\SMPP\ClientAPI;
use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\DateTime;
use PE\Component\SMPP\DTO\Message;
use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\DTO\TLV;
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
    new Session('ID', null, $source = new Address(Address::TON_INTERNATIONAL, Address::NPI_ISDN, '10001112233')),
    new StorageMemory(),
    new Emitter(),
    new Factory($select = new Select(), new SocketFactory($select), null, null, $logger),
    $logger
);
$client->bind('127.0.0.1:2775', PDU::ID_BIND_TRANSMITTER)
    ->then(function () use ($client, $source) {
        $api = new ClientAPI($client);

        $message = new Message('HELLO', new Address(Address::TON_INTERNATIONAL, Address::NPI_ISDN, '10001112244'));
        $message->setSourceAddress($source);
        $message->setScheduledAt((new DateTime())->modify('+5 seconds'));
        $message->setExpiredAt((new DateTime())->modify('+5 days'));
        $message->setParams([
            PDU::KEY_PRIORITY_FLAG => 0xFF,
            TLV::TAG_SOURCE_PORT => new TLV(TLV::TAG_SOURCE_PORT, 8080),
        ]);

        $api
            ->submitSM($message)
            ->then(fn() => $client->exit()/*TODO success*/)
            ->else(fn() => $client->exit()/*TODO failure*/);
    });
$client->wait();
