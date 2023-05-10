<?php

namespace PE\Component\SMPP;

use PE\Component\Loop\LoopInterface;

interface FactoryInterface
{
    public function createDispatcher(callable $dispatch): LoopInterface;

    public function createConnection(string $address, array $context = [], float $timeout = null): Connection4;
}
