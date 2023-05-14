<?php

require_once __DIR__ . '/vendor/autoload.php';

$buffer = '';

// Check result on empty buffer
dump('uint08', @unpack('C', $buffer));
dump('uint16', @unpack('n', $buffer));
dump('uint32', @unpack('N', $buffer));
//TODO collect offsets and create list in format '+00:00'
$dt = new DateTime();
$tzs = DateTimeZone::listIdentifiers();
$off = [];
foreach ($tzs as $tz) {
    $offset = (new DateTime('now', new DateTimeZone($tz)))->getOffset();
    //$off[] = $dt->format('P');
    $off[] = ($offset < 0 ? '-' : '+') . date('H:i', abs($offset));
}
$tzs = array_combine($tzs, $off);
asort($tzs);
dump($tzs);

return;
$timezones = DateTimeZone::listAbbreviations();

$cities = array();
foreach ($timezones as $key => $zones) {
    foreach ($zones as $id => $zone) {
        /**
         * Only get timezones explicitly not part of "Others".
         * @see http://www.php.net/manual/en/timezones.others.php
         */
        if (preg_match('/^(America|Antartica|Arctic|Asia|Atlantic|Europe|Indian|Pacific)\//', $zone['timezone_id'])
            && $zone['timezone_id']) {
            $cities[$zone['timezone_id']][] = $key;
        }
    }
}
foreach ($cities as $key => $value) {
    $cities[$key] = join(', ', $value);
}
$cities = array_unique($cities);
foreach ($cities as $key => &$val) {
    $val = (new DateTimeZone($key))->getOffset(new DateTime());
}
ksort($cities);
dump($cities);
