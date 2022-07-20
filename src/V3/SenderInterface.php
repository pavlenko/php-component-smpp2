<?php

namespace PE\Component\SMPP\V3;

interface SenderInterface
{
    /**
     * Send a SMS
     *
     * @param SMSInterface $message SMS Message
     * @return string
     */
    public function sendSMS(SMSInterface $message): string;
}
