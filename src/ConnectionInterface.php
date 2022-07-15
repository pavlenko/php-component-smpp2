<?php

namespace PE\Component\SMPP;

interface ConnectionInterface
{
    public const STATUS_EXIT = 'EXIT';

    public function getStatus(): string;

    public function setStatus(string $status): void;

    public function open();//TODO <-- server/client/sender specific

    public function bind();//TODO <-- server/client/sender specific

    public function readPDU();

    public function sendPDU($pduData);

    public function exit(): void;
}
