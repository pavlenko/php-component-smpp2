<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Deferred;

interface ClientInterface
{
    /**
     * Connects to server and send bind request
     *
     * @param string $address
     * @param int $mode
     * @return Deferred
     */
    public function bind(string $address, int $mode): Deferred;

    /**
     * Sends raw PDU
     *
     * @param int $id
     * @param array $params
     * @return Deferred
     */
    public function send(int $id, array $params = []): Deferred;

    /**
     * Runs loop for wait incoming data
     */
    public function wait(): void;

    /**
     * Disconnects from server and send unbind request
     */
    public function exit(): void;
}
