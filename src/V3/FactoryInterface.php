<?php

namespace PE\Component\SMPP\V3;

// create server socket?
// create client socket?
// create session?
interface FactoryInterface
{
    /**
     * Create PDU from raw data
     *
     * @param string $body
     *
     * @return PDUInterface
     */
    public function createPDU(string $body): PDUInterface;
}
