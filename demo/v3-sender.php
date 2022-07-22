<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\PDU\Address;

require_once __DIR__ . '/../vendor/autoload.php';

$session = new Session('SENDER');
$factory = new Factory();

$sender = new Sender('127.0.0.1:2775', $factory, $session);
$sender->bind();

$sms = new SMS('HELLO', new Address(0, 0, '+38(066)0660660'));
$sender->sendSMS($sms);
