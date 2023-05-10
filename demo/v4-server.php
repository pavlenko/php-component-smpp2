<?php

namespace PE\Component\SMPP\V4;

use PE\Component\Event\Emitter;
use PE\Component\SMPP\Factory4;
use PE\Component\SMPP\Server4;
use PE\Component\SMPP\Session;
use PE\Component\SMPP\Storage4;
use PE\Component\Socket\Factory;
use PE\Component\Socket\Select;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;

require_once __DIR__ . '/../vendor/autoload.php';

$server = new Server4(
    new Session('SERVER'),
    new Storage4(),
    new Emitter(),
    new Factory4($select = new Select(), new Factory($select)),
    new ConsoleLogger(new ConsoleOutput(ConsoleOutput::VERBOSITY_DEBUG))
);
$server->bind('127.0.0.1:2775');
