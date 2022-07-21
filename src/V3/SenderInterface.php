<?php

namespace PE\Component\SMPP\V3;

interface SenderInterface
{
    public function connect(int $type): void;

    /**
     * Send a SMS
     *
     * @param SMSInterface $message SMS Message
     * @return string
     */
    public function sendSMS(SMSInterface $message): string;

    //init
    // `- connect
    // `- bind session
    //send
    //exit
    // `- unbind session
    // `- close connection
}
