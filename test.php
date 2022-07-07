<?php

namespace PE\SMPP;

use PE\SMPP\PDU;

require_once __DIR__ . '/vendor/autoload.php';

$r1 = fopen('php://temp', 'w+');
$r2 = fopen('php://temp', 'w+');

$a = [$r1 => 'A', $r2 => 'B'];

var_dump($r1, $r2, $a);
