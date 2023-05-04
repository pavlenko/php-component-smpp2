<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Server4;

require_once __DIR__ . '/../vendor/autoload.php';

$server = new Server4();
$server->bind('127.0.0.1:2775');