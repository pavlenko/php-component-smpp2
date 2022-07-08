<?php

namespace PE\SMPP;

use PE\SMPP\Util\Buffer;
use PE\SMPP\Util\Stream;

final class Server
{
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
            foreach ($r as $client) {
                $head = $client->readData(16);

                if ('' === $head) {
                    $client->close();
                    unset($clients[array_search($client, $clients)]);
                    continue;
                }

                $stream = new Buffer($head);
                if ($stream->bytesLeft() < 16) {
                    throw new \RuntimeException('Malformed PDU header');
                }

                $length        = $stream->shiftInt32();
                $commandID     = $stream->shiftInt32();
                $commandStatus = $stream->shiftInt32();
                $sequenceNum   = $stream->shiftInt32();

                $body = (string) $client->readData($length);
                if (strlen($body) < $length - 16) {
                    throw new \RuntimeException('Malformed PDU body');
                }

                new Command($commandID, $commandStatus, $sequenceNum, $body);
            }
        }
    }
}
