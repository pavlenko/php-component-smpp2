<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\Address;
use PE\Component\SMPP\DTO\Message;
use PE\Component\SMPP\DTO\PDU;

final class Storage4 implements StorageInterface
{
    /**
     * @var Message[]
     */
    private array $data = [];

    public function search(
        string $messageID = null,
        Address $sourceAddress = null,
        Address $targetAddress = null,
        bool $checkScheduled = false
    ): ?Message {
        if (null === $messageID && null === $sourceAddress && null === $targetAddress) {
            throw new \UnexpectedValueException(
                'You must pass at least one of $messageID, $sourceAddress, $targetAddress'
            );
        }

        $now = new \DateTime();
        foreach ($this->data as $message) {
            if (null !== $messageID && $message->getMessageID() !== $messageID) {
                continue;
            }
            if (null !== $sourceAddress && (string) $message->getSourceAddress() !== (string) $sourceAddress) {
                continue;
            }
            if (null !== $targetAddress && (string) $message->getTargetAddress() !== (string) $targetAddress) {
                continue;
            }
            if (null !== $checkScheduled && $message->getScheduledAt() > $now) {
                continue;
            }
            return $message;
        }

        return null;
    }

    public function select(Address $address = null): ?PDU
    {
        foreach ($this->data as $pdu) {
            $destination = $pdu->get('dest_address');
            if ($destination instanceof Address && !$address || ($destination->getValue() === $address->getValue())) {
                return $pdu;
            }
        }
        return null;
    }

    public function insert(Message $message): void
    {
        $this->data[spl_object_hash($message)] = $message;
    }

    public function update(Message $message): void
    {
        // TODO: Implement update() method.
    }

    public function delete(PDU $pdu): void
    {
        unset($this->data[spl_object_hash($pdu)]);
    }
}
