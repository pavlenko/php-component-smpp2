<?php

namespace PE\SMPP;

use PE\SMPP\Util\Stream;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class Server
{
    private LoggerInterface $logger;

    public function run(): void
    {
        $master  = Stream::createServer('127.0.0.1');
        $clients = [];

        while (true) {
            // Handle new connections
            $client = $master->accept();
            if ($client) {
                $this->logger->log(LogLevel::INFO, 'New client');
                $clients[] = $client->setBlocking(false);
            }

            foreach ($clients AS $client) {
                if ($client->selectR()) {
                    // Handle read data from client
                    $client->readData(8192);
                }
            }
        }
    }
}
