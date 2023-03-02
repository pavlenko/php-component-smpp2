<?php

namespace PE\Component\SMPP\Tests\Util;

use PE\Component\SMPP\Util\Events;
use PHPUnit\Framework\TestCase;

class EventsTest extends TestCase
{
    public function testTrigger(): void
    {
        $events = new Events();
        $count  = 0;

        $handler = function () use (&$count) {
            $count++;
            return false;
        };

        $events->trigger('event');

        self::assertSame(0, $count);

        $events->attach('event', $handler);
        $events->attach('event', $handler2 = fn() => 1);
        $events->trigger('event');

        self::assertSame(1, $count);

        $events->detach('event', $handler);
        $events->detach('event', $handler2);
        $events->detach('event', $handler2);
        $events->trigger('event');

        self::assertSame(1, $count);
    }
}