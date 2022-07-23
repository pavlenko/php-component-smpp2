<?php

namespace PE\Component\SMPP\V3;

// Server daemon (can be based on client but with multiple sessions)
// - accept multiple clients
// - handle PDU types via event listeners
interface ServerInterface
{
    //init
    // `- connect
    //tick
    // `- select - maybe wrap stream into connection for common usage
    // `- processAccept - add connection without session
    // `- processReceive($session) - if bind received - add session to connection
    // `- processTimeout($session, $sentPDUs)
    // `- processWaiting($session, $waitPDUs)
    //exit
    // `- unbind sessions
    // `- close connection

    /**
     * Connect to socket
     */
    public function bind(): void;

    /**
     * Dispatch connection
     */
    public function tick(): void;

    /**
     * Close all sessions & stop server
     */
    public function exit(): void;
}
