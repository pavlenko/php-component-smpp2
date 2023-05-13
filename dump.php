<?php

require_once __DIR__ . '/vendor/autoload.php';

$buffer = '';

// Check result on empty buffer
dump('uint08', @unpack('C', $buffer));
dump('uint16', @unpack('n', $buffer));
dump('uint32', @unpack('N', $buffer));
dump((new \PE\Component\SMPP\DTO\Address(0b00000111, 0, 'ff'))->dump());
