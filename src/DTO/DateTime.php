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
        //TODO maybe force convert to utc
        return sprintf('DateTime(%s)', $this->format(DATE_ATOM));
    }
}
