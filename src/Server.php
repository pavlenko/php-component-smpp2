<?php

namespace PE\SMPP;

use PE\SMPP\Util\Buffer;
use PE\SMPP\Util\Stream;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Server
{
    private LoggerInterface $logger;
    private ?Stream $master = null;

    /**
     * @var Stream[]
     */
    private array $clients = [];

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    public function init(string $address): void
    {
        $this->master = Stream::createServer($address);
        $this->master->setBlocking(false);

        if (0 === strpos($address, 'tls')) {
            $this->master->setCrypto(true, STREAM_CRYPTO_METHOD_TLS_SERVER);
        }
    }

    public function stop(): void
    {
        foreach ($this->clients as $client) {
            $client->close();
        }

        if ($this->master) {
            $this->master->close();
            $this->master = null;
        }
    }

    //TODO rename to tick & remove infinite loop
    public function run(): void
    {
        $master  = Stream::createServer('127.0.0.1');
        $clients = [];

        while (true) {
            $r = array_merge([$master], $clients);
            $n = null;

            // Check streams first
            if (0 === Stream::select($r, $n, $n)) {
                continue;
            }

            // Handle new connections
            if (in_array($master, $r)) {
                unset($r[array_search($master, $r)]);
                $clients[] = $master->accept();
            }

            // Handle read data
            //TODO split loop to foreach($r as $client){} && foreach($clients as $client){ if(in_array($client, $r)){continue} }
            foreach ($clients as $key => $client) {
                if (in_array($client, $r)) {
                    $head = $client->readData(16);

                    if ('' === $head) {
                        $client->close();
                        unset($clients[$key]);
                        continue;
                    }

                    $buffer = new Buffer($head);
                    if ($buffer->bytesLeft() < 16) {
                        throw new \RuntimeException('Malformed PDU header');
                    }

                    $length        = $buffer->shiftInt32();
                    $commandID     = $buffer->shiftInt32();
                    $commandStatus = $buffer->shiftInt32();
                    $sequenceNum   = $buffer->shiftInt32();

                    $body = (string) $client->readData($length);
                    if (strlen($body) < $length - 16) {
                        throw new \RuntimeException('Malformed PDU body');
                    }

                    new Command($commandID, $commandStatus, $sequenceNum, $body);
                } else {
                    //TODO create enquire link pdu
                    //TODO maybe add to session: __construct(Stream), readPDU(): PDU, sendPDU(PDU): void
                    $num = $client->sendData('ENQUIRE_LINK');
                    if (0 === $num) {
                        $client->close();
                        unset($clients[$key]);
                    }
                }
            }
        }
    }
}
