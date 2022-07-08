<?php

namespace PE\SMPP;

require_once __DIR__ . '/vendor/autoload.php';

$c1 = new \stdClass();
$c1->val = 1;
$c2 = new \stdClass();
$c2->val = 2;

$s = new \SplObjectStorage();
$s->attach($c1, 'A');
$s->attach($c2, 'B');

var_dump(array_map('serialize', $s));
