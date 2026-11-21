<?php

namespace Dev\Tests\Fixtures\Listener;

use Snidget\Kernel\PSR\Event\KernelEvent;
use Snidget\Kernel\PSR\Event\Listen;

enum FixtureEvent
{
    case START;
}

class EventListener
{
    /** @var list<string> */
    public static array $calls = [];

    #[Listen(KernelEvent::START)]
    public function onKernelStart(): void
    {
        self::$calls[] = 'kernel start';
    }
}
