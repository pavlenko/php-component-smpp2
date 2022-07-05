<?php

namespace PE\SMPP;

use PE\SMPP\PDU;

require_once __DIR__ . '/vendor/autoload.php';

$conn = new Connection('127.0.0.1');
$conn->connect();

$bindTransmitter = new PDU\BindTransmitter();
$bindTransmitter->setSystemId('username');
$bindTransmitter->setPassword('password');

$resp = $conn->sendPDU($bindTransmitter, PDU\BindTransmitterResp::class);
var_dump($resp);

$conn->exit();
