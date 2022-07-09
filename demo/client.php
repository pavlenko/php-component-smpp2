<?php

namespace PE\SMPP;

$client = new Client('user', 'pass');
$client->init('127.0.0.1:2775');

//TODO allow call below
$client->send(/*TODO src_addr, dst_addr, message*/)->wait();

$client->stop();
