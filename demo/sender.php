<?php

namespace PE\Component\SMPP;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

$address  = 'tcp://127.0.0.1:2775' || 'tls://127.0.0.1:8775';
$systemID = 'SENDER_SYSTEM_ID';
$password = 'PASSWORD';

$logger  = new NullLogger();
$storage = [];// InMemory|Redis|MySQL(?)

// V1
$sender = new Sender($address, $systemID, $password, $logger, $storage);
$sender->send('1234', '1234', 'MESSAGE');

// V2
$sender = new Sender($address, $systemID, $password, $logger, $storage);
$sender->bind($type);// Transmitter, etc
$sender->send('1234', '1234', 'MESSAGE');

interface StorageInterface {}

class StorageNull implements StorageInterface {}

class Sender
{
    private string $address;
    private string $systemID;
    private string $password;

    private LoggerInterface $logger;
    private StorageInterface $storage;

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

    public function send($from, $to, string $message): array
    {
        return [];
    }
}
