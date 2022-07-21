<?php

namespace PE\Component\SMPP\V3;

use PE\Component\SMPP\Util\Stream;

class Client extends ClientBase
{
    public function tick(): void
    {
        $r = [$this->connection->getStream()];
        $n = [];
        Stream::select($r, $n, $n, 0.05);

        if (!empty($r)) {
            $this->handleReceive($this->session);
        }

        $this->handleTimeout($this->session);
        $this->handlePending($this->session);
    }
}
