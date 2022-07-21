<?php

namespace PE\Component\SMPP\V3;

interface SenderInterface
{
    /**
     * Connects to server and send bind request with result check
     */
    public function bind(): void;

    /**
     * Send a SMS
     *
     * @param SMSInterface $message SMS Message
     *
     * @return string
     */
    public function sendSMS(SMSInterface $message): string;

    /**
     * Disconnects from server and send unbind request with result check
     */
    public function exit(): void;
}
