<?php

require_once __DIR__ . '/vendor/autoload.php';

$buffer = "HELLO";
$pos = 0;

function decodeString(string $buffer, int &$pos, ?int $min, ?int $max): string
{
    $data = '';
    while (strlen($buffer) > $pos) {
        $data .= $buffer[$pos++];
        if ("\n" === $buffer[$pos - 1]) {
            dump('stop by NUL');
            break;
        }
        // Check max chars for read, usable for TLV with no NUL terminated strings
        if (strlen($data) === $max) {
            dump('stop by max');
            break;
        }
    }
    $pos++;
    if (null === $max && "\0" !== substr($data, -1)) {
        dump('not null terminated');//TODO to validator, malformed
    }
    if (null !== $min && strlen($data) < $min) {
        dump('too short');//TODO to validator, invalid???
    }
    if ($data === $buffer) {
        dump('stop by EOF');//just info
    }
    return $data;
}

$str = decodeString($buffer, $pos, null, null);
dump($str, strlen($str), $pos - 1);
