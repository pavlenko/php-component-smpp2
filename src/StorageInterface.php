<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Message;

interface StorageInterface
{
    public function search(Search $search): ?Message;

    public function select(): ?Message;

    public function insert(Message $message): void;

    public function update(Message $message): void;

    public function delete(Message $message): void;
}
