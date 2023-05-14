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
        return new self($value->format(DATE_RFC3339_EXTENDED));
    }

    public function dump(): string
    {
        return sprintf(
            'DateTime(date: %s, time: %s, tz: %s)',
            $this->format('Y-m-d'),
            $this->format('H:i:s.') . $this->format('v')[0],
            'UTC' . ($this->getOffset() < 0 ? '-' : '+') . gmdate('H:i', abs($this->getOffset()))
        );
    }
}
