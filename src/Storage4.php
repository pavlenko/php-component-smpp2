<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Message;

final class Storage4 implements StorageInterface
{
    /**
     * @var Message[]
     */
    private array $messages = [];

    public function search(Search $search): ?Message
    {
        $now = new \DateTime();
        foreach ($this->messages as $message) {
            if (null !== $search->getMessageID() && $message->getMessageID() !== $search->getMessageID()) {
                continue;
            }
            if (null !== $search->getSourceAddress()
                && (string) $message->getSourceAddress() !== (string) $search->getSourceAddress()) {
                continue;
            }
            if (null !== $search->getTargetAddress()
                && (string) $message->getTargetAddress() !== (string) $search->getTargetAddress()) {
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
