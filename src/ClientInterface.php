<?php

namespace PE\Component\SMPP;

// Client daemon
// - catch inbound PDU
// - send scheduled PDU
interface ClientInterface
{
    //init
    // `- connect
    // `- bind session
    //tick
    // `- select
    // `- processReceive($session)
    // `- processTimeout($session, $sentPDUs)
    // `- processWaiting($session, $waitPDUs)
    //exit
    // `- unbind session
    // `- close connection

    /**
     * Connects to server and send bind request with result check
     */
    public function bind(): void;

    /**
     * Dispatch connection
     */
    public function tick(): void;

    /**
     * Disconnects from server and send unbind request with result check
     */
    public function exit(): void;
}
