<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\Util\Stream;
use PE\Component\SMPP\V3\ConnectionInterface;
use PE\Component\SMPP\V3\PDUInterface;

class Client implements ClientInterface
{
    use ClientTrait;
    use Events;

    public const EVENT_RECEIVE = 'server.receive';

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
        if (PDUInterface::STATUS_NO_ERROR !== $pdu->getStatus()) {
            throw new \UnexpectedValueException('Error', $pdu->getStatus());
        }
        $this->emit(self::EVENT_RECEIVE, $connection, $pdu);//TODO extension point
    }
}
