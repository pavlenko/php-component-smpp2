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
    // `- select
    // `- processAccept
    // `- processReceive($session)
    // `- processTimeout($session, $sentPDUs)
    // `- processWaiting($session, $waitPDUs)
    //exit
    // `- unbind sessions
    // `- close connection
}
