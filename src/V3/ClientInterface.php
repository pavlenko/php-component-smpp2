<?php

namespace PE\Component\SMPP\V3;

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
}
