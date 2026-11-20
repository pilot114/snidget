<?php

namespace App\Module\Async;

use Fiber;
use Io\Poll\Context;
use Io\Poll\Event;
use Io\Poll\Watcher;
use SplQueue;
use StreamPollHandle;
use Time\Duration;

class Scheduler
{
    /** @var SplQueue<Fiber<mixed, mixed, mixed, mixed>> */
    public SplQueue $fibers;

    protected ?Debug $debug = null;

    protected Context $poll;

    /**
     * Один наблюдатель на сокет (Context не даёт добавить сокет дважды),
     * в его data — ждущие файберы по событиям: ['Read' => [Fiber, ...], 'Write' => [...]]
     *
     * @var array<int, Watcher>
     */
    protected array $watchers = [];

    // служебные файберы (ioPoll, Debug) крутятся в очереди всегда
    protected int $serviceFibers = 1;

    /**
     * @param list<callable(): mixed> $cbs
     */
    public function __construct(array $cbs, ?Debug $debug = null)
    {
        if ($debug instanceof Debug) {
            $this->debug = $debug;
            $cbs[] = $debug->print(...);
            $this->serviceFibers++;
        }

        $cbs[] = $this->ioPoll(...);
        $this->poll = new Context();

        $this->fibers = new SplQueue();
        foreach ($cbs as $cb) {
            $fiber = new Fiber($cb);
            $this->fibers->enqueue($fiber);
        }
    }

    public function run(): never
    {
        pcntl_async_signals(true);
        $isTerminate = false;
        pcntl_signal(SIGTERM, function () use (&$isTerminate): void {
            $isTerminate = true;
        });

        while (!$this->fibers->isEmpty() && !$isTerminate) {
            $fiber = $this->fibers->dequeue();

            if ($fiber->isTerminated()) {
                continue;
            }

            $result = $this->execute($fiber);

            if (is_callable($result)) {
                $this->fibers->enqueue(new Fiber($result));
                $result = [Wait::ASAP];
            }

            if (!is_array($result)) {
                continue;
            }

            match ($result[0]) {
                Wait::ASAP  => $this->fibers->enqueue($fiber),
                Wait::READ  => $this->wait($fiber, Event::Read, $result[1]),
                Wait::WRITE => $this->wait($fiber, Event::Write, $result[1]),
                default => null,
            };
        }

        exit("fibers queue empty. exit...\n");
    }

    /**
     * @param Fiber<mixed, mixed, mixed, mixed> $fiber
     */
    protected function execute(Fiber $fiber): mixed
    {
        if ($this->debug instanceof Debug) {
            $this->debug->beforeFiber((int)hrtime(true), $fiber);
        }

        if ($fiber->isStarted()) {
            $result = $fiber->isSuspended() ? $fiber->resume() : null;
        } else {
            $result = $fiber->start();
        }

        if ($this->debug instanceof Debug) {
            $this->debug->afterFiber((int)hrtime(true), $fiber);
        }

        return $result;
    }

    public static function suspend(Wait $type, mixed $payload = null): mixed
    {
        $args = $payload === null ? [$type] : [$type, $payload];
        return Fiber::suspend($args);
    }

    /**
     * @param callable(): mixed $cb
     */
    public static function fork(callable $cb): mixed
    {
        return Fiber::suspend($cb);
    }

    /**
     * @param Fiber<mixed, mixed, mixed, mixed> $fiber
     */
    protected function wait(Fiber $fiber, Event $event, mixed $socket): void
    {
        if (!is_resource($socket)) {
            throw new \InvalidArgumentException('Файбер ждёт I/O, но не передал сокет');
        }

        $watcher = $this->watchers[(int)$socket] ?? null;
        if ($watcher === null) {
            $handle = new StreamPollHandle($socket);
            $this->watchers[(int)$socket] = $this->poll->add($handle, [$event], [$event->name => [$fiber]]);
            return;
        }

        /** @var array<string, list<Fiber<mixed, mixed, mixed, mixed>>> $waiting */
        $waiting = $watcher->getData();
        $waiting[$event->name][] = $fiber;
        $watcher->modify(self::events($waiting), $waiting);
    }

    /**
     * Добавляет в очередь файберы, которые ожидают операций I/O
     */
    protected function ioPoll(): never
    {
        while (true) {
            Scheduler::suspend(Wait::ASAP);
            if ($this->watchers === []) {
                continue;
            }

            // в очереди только служебные файберы: ждём I/O, а не крутим цикл вхолостую
            $isIdle = count($this->fibers) < $this->serviceFibers;
            foreach ($this->poll->wait(Duration::fromSeconds($isIdle ? 1 : 0)) as $watcher) {
                $this->wake($watcher);
            }
        }
    }

    protected function wake(Watcher $watcher): void
    {
        // ошибка или разрыв будит всех: чтение получит EOF, запись — ошибку
        $isBroken = $watcher->hasTriggered(Event::Error) || $watcher->hasTriggered(Event::HangUp);
        /** @var array<string, list<Fiber<mixed, mixed, mixed, mixed>>> $waiting */
        $waiting = $watcher->getData();
        foreach ([Event::Read, Event::Write] as $event) {
            if ($isBroken || $watcher->hasTriggered($event)) {
                array_map($this->fibers->enqueue(...), $waiting[$event->name] ?? []);
                unset($waiting[$event->name]);
            }
        }

        if ($waiting === []) {
            /** @var StreamPollHandle $handle */
            $handle = $watcher->getHandle();
            unset($this->watchers[(int)$handle->getStream()]);
            $watcher->remove();
            return;
        }

        $watcher->modify(self::events($waiting), $waiting);
    }

    /**
     * @param array<string, mixed> $waiting
     * @return list<Event>
     */
    protected static function events(array $waiting): array
    {
        return array_values(array_filter([Event::Read, Event::Write], fn(Event $e): bool => isset($waiting[$e->name])));
    }
}
