<?php

require_once __DIR__ . '/vendor/autoload.php';

$buffer = '';

// Check result on empty buffer
dump('uint08', @unpack('C', $buffer));
dump('uint16', @unpack('n', $buffer));
dump('uint32', @unpack('N', $buffer));
dump('5' > 4, '5' < 6, '5' < 4);
