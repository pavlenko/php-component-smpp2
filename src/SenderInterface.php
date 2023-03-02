<?php

namespace PE\Component\SMPP;

use PE\Component\SMPP\DTO\SMS;

interface SenderInterface
{
    /**
     * Connects to server and send bind request with result check
     */
    public function bind(): void;

    /**
     * Send a SMS
     *
     * @param SMS $message SMS Message
     *
     * @return string
     */
    public function sendSMS(SMS $message): string;

    /**
     * Disconnects from server and send unbind request with result check
     */
    public function exit(): void;
}
