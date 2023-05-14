<?php

namespace PE\Component\SMPP\V4;

use PE\Component\Event\Emitter;
use PE\Component\SMPP\Client4;
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
$client = new Client4(
    new Session('ID', null, $source = new Address(Address::TON_INTERNATIONAL, Address::NPI_ISDN, '10001112233')),
    new StorageMemory(),
    new Emitter(),
    new Factory($select = new Select(), new SocketFactory($select), null, null, $logger),
    $logger
);
$client->bind('127.0.0.1:2775', PDU::ID_BIND_TRANSMITTER)
    ->then(function () use ($client, $source) {
        $api = new ClientAPI($client);

        //TODO message required on submit_sm, replace_sm
        //TODO dst_address required on submit_sm, replace_sm, data_sm
        //TODO src_address required on cancel_sm, replace_sm
        //TODO message_id required on query_sm, replace_sm, cancel_sm
        $message = new Message();
        $message->setScheduledAt((new DateTime())->modify('+5 seconds'));
        $message->setExpiredAt((new DateTime())->modify('+5 days'));

        $api
            ->submitSM([
                PDU::KEY_SHORT_MESSAGE   => 'HELLO',
                PDU::KEY_SRC_ADDRESS     => $source,
                PDU::KEY_DST_ADDRESS     => new Address(Address::TON_INTERNATIONAL, Address::NPI_ISDN, '10001112244'),
                PDU::KEY_SCHEDULED_AT    => (new DateTime())->modify('+5 seconds'),
                PDU::KEY_VALIDITY_PERIOD => (new DateTime())->modify('+5 days'),
                TLV::TAG_SOURCE_PORT     => new TLV(TLV::TAG_SOURCE_PORT, 8080),
            ])
            ->then(fn() => $client->exit()/*TODO success*/)
            ->else(fn() => $client->exit()/*TODO failure*/);
    });
$client->wait();
