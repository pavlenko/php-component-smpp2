<?php

namespace PE\Component\SMPP;

interface FactoryInterface
{
    public function createConnection(string $address, array $context = [], float $timeout = null): Connection4;
}
