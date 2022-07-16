<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Exception\ExceptionInterface;

// create server socket?
// create client socket?
// create session?
interface FactoryInterface
{
    /**
     * Create PDU from raw data
     *
     * @param int    $id
     * @param int    $status
     * @param string $body
     *
     * @return PDUInterface
     *
     * @throws ExceptionInterface
     */
    public function createPDU(int $id, int $status, string $body): PDUInterface;
}
