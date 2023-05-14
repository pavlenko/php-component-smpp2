<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Message;

interface StorageInterface
{
    /**
     * Search message by criteria
     *
     * @param Criteria $search
     * @return Message|null
     */
    public function search(Criteria $search): ?Message;

    /**
     * Get single message
     *
     * @return Message|null
     * @deprecated
     */
    public function select(): ?Message;

    /**
     * Add message to storage
     *
     * @param Message $message
     */
    public function insert(Message $message): void;

    /**
     * Update message in storage
     *
     * @param Message $message
     */
    public function update(Message $message): void;

    /**
     * Delete message from storage
     *
     * @param Message $message
     */
    public function delete(Message $message): void;
}
