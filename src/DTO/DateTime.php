<?php

namespace PE\Component\SMPP\DTO;

final class DateTime extends \DateTime
{
    /**
     * @param string $format
     * @param string $datetime
     * @param \DateTimeZone|null $timezone
     * @return self|false
     * @throws \Exception
     */
    public static function createFromFormat($format, $datetime, \DateTimeZone $timezone = null)
    {
        $value = parent::createFromFormat($format, $datetime, $timezone);
        if (false === $value) {
            return false;
        }
        $datetime = new self(null, $value->getTimezone());
        $datetime->setTimestamp($value->getTimestamp());
        return $datetime;
    }

    public function dump(): string
    {
        $datetime = clone $this;
        $datetime->setTimezone(new \DateTimeZone('UTC'));
        return sprintf(
            'DateTime(date: %s, time: %s, tz: UTC)',
            $datetime->format('Y-m-d'),
            $datetime->format('H:i:s')
        );
    }
}
