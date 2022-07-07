<?php

namespace PE\SMPP;

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
            foreach ($clients as $client) {
                //TODO handle incoming data in some way (maybe read to internal buffer for each stream)
                $client->readData(8192);
                //TODO handle close client connections if result of data is empty
            }
        }
    }
}
