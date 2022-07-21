<?php

namespace PE\Component\SMPP\V3;

require_once __DIR__ . '/../vendor/autoload.php';

$session = new Session('SENDER');
$factory = new Factory();

$sender = new Sender('127.0.0.1:2775', $factory, $session);
$sender->bind();
