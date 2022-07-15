<?php

namespace PE\Component\SMPP;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class Sender
{
    private string $address;
    private string $systemID;
    private string $password;

    private LoggerInterface $logger;
    private StorageInterface $storage;

    private ConnectionInterface $connection;

    public function __construct(
        string $address,
        string $systemID,
        string $password,
        LoggerInterface $logger = null,
        StorageInterface $storage = null
    ) {
        $this->address  = $address;
        $this->systemID = $systemID;
        $this->password = $password;
        $this->logger   = $logger ?: new NullLogger();
        $this->storage  = $storage ?: new StorageNull();
    }

    public function send($from, $to, string $message): string
    {
        if ($this->connection->getStatus() == 'init') {
            $this->connection->open();// status -> open
            $this->connection->sendPDU('BIND');
            $this->connection->readPDU('BIND_RESP');// status -> bound
        }
        $this->connection->sendPDU('SUBMIT_SM');
        return $this->connection->readPDU('SUBMIT_SM_RESP')['message_id'];
    }

    public function exit()
    {
        $this->connection->sendPDU('UNBIND');
        $this->connection->readPDU('UNBIND_RESP');
        $this->connection->exit();//<-- may be reset here for allow re-connect
    }
}
