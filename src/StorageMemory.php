<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Message;

final class StorageMemory implements StorageInterface
{
    /**
     * @var Message[]
     */
    private array $messages = [];

    public function search(Criteria $search): ?Message
    {
        $now = new \DateTime();
        foreach ($this->messages as $message) {
            if (null !== $search->getMessageID() && $message->getID() !== $search->getMessageID()) {
                continue;
            }
            if (null !== $search->getSourceAddress()
                && $message->getSourceAddress()->dump() !== $search->getSourceAddress()->dump()) {
                continue;
            }
            if (null !== $search->getTargetAddress()
                && $message->getTargetAddress()->dump() !== $search->getTargetAddress()->dump()) {
                continue;
            }
            if ($search->isCheckSchedule() && $message->getScheduledAt() > $now) {
                continue;
            }
            if (null !== $search->getStatus() && $message->getStatus() !== $search->getStatus()) {
                continue;
            }
            return $message;
        }

        return null;
    }

    /* @deprecated */
    public function select(): ?Message
    {
        return array_shift($this->messages);
    }

    public function insert(Message $message): void
    {
        $this->messages[spl_object_hash($message)] = $message;
    }

    public function update(Message $message): void
    {
        $this->messages[spl_object_hash($message)] = $message;
    }

    public function delete(Message $message): void
    {
        unset($this->messages[spl_object_hash($message)]);
    }
}
