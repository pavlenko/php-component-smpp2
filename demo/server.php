<?php

namespace PE\SMPP;

$server = new Server();
$server->init('127.0.0.1:2775');//TODO move to construct

$time = time();
while (true) {
    if (time() - $time > 20) {
        break;
    }
    $server->tick();
    sleep(1);
}
$server->stop();
