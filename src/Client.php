<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\PDU;
use PE\Component\SMPP\Util\Stream;

final class Client implements ClientInterface
{
    use ClientTrait;

    public const EVENT_RECEIVE = 'client.receive';

    public function tick(): void
    {
        $r = [$this->connection->getStream()];
        $n = [];
        Stream::select($r, $n, $n, 0.05);

        if (!empty($r)) {
            $this->processReceive($this->connection);
        }

        $this->handleTimeout($this->session);
        $this->handlePending($this->session);
    }

    private function processReceive(ConnectionInterface $connection)
    {
        $pdu = $connection->readPDU();
        if (null === $pdu) {
            //TODO $this->detachConnection($connection, false);
            return;
        }
        if (PDU::STATUS_NO_ERROR !== $pdu->getStatus()) {
            throw new \UnexpectedValueException('Error', $pdu->getStatus());
        }
        $this->events->trigger(self::EVENT_RECEIVE, $connection, $pdu);
    }
}
