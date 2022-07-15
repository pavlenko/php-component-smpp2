<?php

namespace PE\Component\SMPP;

interface ConnectionInterface
{
    public const STATUS_EXIT = 'EXIT';

    public function getStatus(): string;

    public function setStatus(string $status): void;

    public function open();//TODO <-- server/client/sender specific

    public function bind();//TODO <-- server/client/sender specific

    // Read PDU (any)
    public function readPDU(): string;

    // Send PDU
    public function sendPDU(int $id, int $seqNum, string $body): void;

    // Wait PDU (and check expected response)
    public function waitPDU(int $id, int $seqNum): string;//timeout?

    public function exit(): void;
}
