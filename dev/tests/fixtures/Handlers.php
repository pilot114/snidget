<?php

namespace Snidget\Tests\Fixtures;

use Closure;
use Psr\EventDispatcher\StoppableEventInterface;
use RuntimeException;
use Snidget\HTTP\Bind;
use Snidget\HTTP\Request;

class FixtureMiddleware
{
    /** @param Closure(Request): mixed $next */
    public function wrap(Request $request, Closure $next): string
    {
        $result = $next($request);
        return 'wrapped ' . (is_string($result) ? $result : '');
    }
}

class FixtureController
{
    #[Bind(class: FixtureMiddleware::class, method: 'wrap')]
    public function one(): string
    {
        return 'one';
    }

    #[Bind(class: FixtureMiddleware::class)]
    public function two(): string
    {
        return 'two';
    }

    /** @return array<string, int> */
    public function arr(): array
    {
        return ['a' => 1];
    }

    public function boom(): never
    {
        throw new RuntimeException('boom');
    }
}

class StopEvent implements StoppableEventInterface
{
    public int $calls = 0;

    public function isPropagationStopped(): bool
    {
        return $this->calls > 0;
    }
}

class FixtureListener
{
    /** @var list<mixed> */
    public static array $received = [];

    public function onData(mixed $data): void
    {
        self::$received[] = $data;
    }

    public function onEvent(StopEvent $event): void
    {
        $event->calls++;
    }
}

class ReplaceMiddleware
{
    /** @param Closure(Request): mixed $next */
    public function replace(Request $request, Closure $next): mixed
    {
        $replaced = new Request();
        $replaced->uri = 'replaced';
        return $next($replaced);
    }
}

class UriController
{
    public function uri(Request $request): string
    {
        return $request->uri;
    }

    #[Bind(class: ReplaceMiddleware::class, method: 'replace')]
    public function replaced(Request $request): string
    {
        return $request->uri;
    }
}

interface Clock
{
}

class SystemClock implements Clock
{
}

class NeedsClock
{
    public function __construct(public Clock $clock)
    {
    }
}

class MaybeClock
{
    public function __construct(public ?Clock $clock = null)
    {
    }
}
