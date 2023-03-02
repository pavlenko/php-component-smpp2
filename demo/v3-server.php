<?php

namespace PE\Component\SMPP\V3;

use PE\Component\Loop\Loop;
use PE\Component\SMPP\ConnectionInterface;
use PE\Component\SMPP\Factory;
use PE\Component\SMPP\Body;
use PE\Component\SMPP\PDUInterface;
use PE\Component\SMPP\Server;
use PE\Component\SMPP\Session;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$factory = new Factory();
$session = new Session('SERVER');
$logger  = new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG));

$server = new Server('127.0.0.1:2775', $factory, $session, $logger);
$server->on(Server::EVENT_RECEIVE, function (ConnectionInterface $connection, PDUInterface $pdu) {
    var_dump($pdu);
    if ($pdu->getID() === PDUInterface::ID_SUBMIT_SM) {
        $connection->sendPDU(new PDU(PDUInterface::ID_SUBMIT_SM_RESP, 0, $pdu->getSeqNum(), [
            'message_id' => sprintf(//<-- simple UUID generator
                '%04X%04X%04X%04X%04X%04X%04X%04X',
                // 32 bits for "time_low"
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                // 16 bits for "time_mid"
                mt_rand(0, 0xffff),
                // 16 bits for "time_hi_and_version",
                // four most significant bits holds version number 4
                mt_rand(0, 0x0fff) | 0x4000,
                // 16 bits, 8 bits for "clk_seq_hi_res",
                // 8 bits for "clk_seq_low",
                // two most significant bits holds zero and one for variant DCE1.1
                mt_rand(0, 0x3fff) | 0x8000,
                // 48 bits for "node"
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0xffff)
            ),
        ]));
    }
});
$server->bind();

$loop = new Loop();
$loop->addPeriodicTimer(0.01, fn() => $server->tick());
$loop->addSingularTimer(10, fn() => $loop->stop());
$loop->run();

$server->exit();
