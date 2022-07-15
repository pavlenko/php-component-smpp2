<?php

namespace PE\Component\SMPP;

$address  = 'tcp://127.0.0.1:2775' || 'tls://127.0.0.1:8775';
$systemID = 'SENDER_SYSTEM_ID';
$password = 'PASSWORD';

$logger  = null;
$storage = null;

// V1
$sender = new Sender($address, $systemID, $password, $logger, $storage);
$sender->send('1234', '1234', 'MESSAGE');

// V2
$sender = new Sender($address, $systemID, $password, $logger, $storage);
$sender->send('1234', '1234', 'MESSAGE');
