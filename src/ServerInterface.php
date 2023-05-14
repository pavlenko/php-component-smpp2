<?php

namespace PE\Component\SMPP;

interface ServerInterface
{
    /**
     * Listen to socket
     *
     * @param string $address
     * @return void
     */
    public function bind(string $address): void;

    /**
     * Close all sessions & stop server
     */
    public function stop(): void;
}
