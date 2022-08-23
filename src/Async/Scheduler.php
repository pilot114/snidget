<?php

namespace Snidget\Async;

use SplQueue;
use Fiber;
use Snidget\Enum\Wait;

class Scheduler
{
    public SplQueue $fibers;

    protected ?Debug $debug;
    protected array $waitingForRead = [];
    protected array $waitingForWrite = [];

    public function __construct(array $cbs, Debug $debug = null)
    {
        if ($debug) {
            $this->debug = $debug;
            $cbs[] = $debug->print(...);
        }
        $cbs[] = $this->ioPoll(...);

        $this->fibers = new SplQueue();
        foreach ($cbs as $cb) {
            $fiber = new Fiber($cb);
            $this->fibers->enqueue($fiber);
        }
    }

    public function run(): void
    {
        pcntl_async_signals(true);
        $isTerminate = false;
        pcntl_signal(SIGTERM, function() use (&$isTerminate) {
            $isTerminate = true;
        });

        while (!$this->fibers->isEmpty() && !$isTerminate) {
            usleep(0);
            $fiber = $this->fibers->dequeue();

            if ($fiber->isTerminated()) {
                continue;
            }

            $this->debug && $this->debug->beforeFiber(
                hrtime(true),
                $this->fibers,
                $fiber
            );
            if ($fiber->isStarted()) {
                $result = $fiber->isSuspended() ? $fiber->resume() : null;
            } else {
                $result = $fiber->start();
            }
            $this->debug && $this->debug->afterFiber(hrtime(true));

            if (is_callable($result)) {
                $this->fibers->enqueue(new Fiber($result));
                $result = [Wait::ASAP];
            }
            if (!is_array($result)) {
                continue;
            }
            $type = $result[0];

            match ($type) {
                Wait::ASAP  => $this->fibers->enqueue($fiber),
                Wait::READ  => $this->wait($result[1], $fiber, Wait::READ),
                Wait::WRITE => $this->wait($result[1], $fiber, Wait::WRITE),
                default => null,
            };
        }
        exit("fibers queue empty. exit...\n");
    }

    static public function suspend(Wait $type, $payload = null): mixed
    {
        $args = $payload ? [$type, $payload] : [$type];
        return Fiber::suspend($args);
    }

    static public function fork(callable $cb)
    {
        return Fiber::suspend($cb);
    }

    protected function wait($socket, $fiber, Wait $type): void
    {
        $tmp = function($socket, $fiber, &$wait) {
            $socketId = (int) $socket;
            if (!isset($wait[$socketId])) {
                $wait[$socketId] = [$socket, []];
            }
            $wait[$socketId][1][] = $fiber;
        };
        if ($type === Wait::WRITE) {
            $tmp($socket, $fiber, $this->waitingForWrite);
        }
        if ($type === Wait::READ) {
            $tmp($socket, $fiber, $this->waitingForRead);
        }
    }

    /**
     * Добавляет в очередь файберы, которые ожидают операций I/O
     */
    protected function ioPoll(): void
    {
        /** @phpstan-ignore-next-line */
        while (true) {
            Scheduler::suspend(Wait::ASAP);

            if (!$this->waitingForRead && !$this->waitingForWrite) {
                continue;
            }

            $rSocks = array_map(fn($x) => $x[0], $this->waitingForRead);
            $wSocks = array_map(fn($x) => $x[0], $this->waitingForWrite);
            $eSocks = [];

            $timeout = $this->fibers->isEmpty() ? null : 0;
            if (!stream_select($rSocks, $wSocks, $eSocks, $timeout)) {
                continue;
            }

            foreach ($rSocks as $socket) {
                array_map(fn($fiber) => $this->fibers->enqueue($fiber), $this->waitingForRead[(int) $socket][1]);
                unset($this->waitingForRead[(int) $socket]);
            }

            foreach ($wSocks as $socket) {
                array_map(fn($fiber) => $this->fibers->enqueue($fiber), $this->waitingForWrite[(int) $socket][1]);
                unset($this->waitingForWrite[(int) $socket]);
            }
        }
    }
}