<?php

require_once __DIR__ . '/vendor/autoload.php';

$buffer = '';

// Check result on empty buffer
dump('uint08', @unpack('C', $buffer));
dump('uint16', @unpack('n', $buffer));
dump('uint32', @unpack('N', $buffer));
dump(trim(new stdClass()));
