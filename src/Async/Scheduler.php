<?php

namespace Snidget\Async;

use SplQueue;
use Fiber;
use Snidget\Enum\Wait;

class Scheduler
{
    public SplQueue $fibers;

    protected ?Debug $debug;
    protected array $waitingRead = [];
    protected array $waitingWrite = [];
    protected array $waitingDelay = [];

    public function __construct(array $cbs, Debug $debug = null)
    {
        if ($debug) {
            $this->debug = $debug;
            $cbs[] = $debug->print(...);
        }
        $cbs[] = $this->ioPoll(...);
        $cbs[] = $this->timerPoll(...);

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
                Wait::READ  => $this->wait($fiber, $this->waitingRead, $result[1], (int)$result[1]),
                Wait::WRITE => $this->wait($fiber, $this->waitingWrite, $result[1], (int)$result[1]),
                Wait::DELAY => $this->wait($fiber, $this->waitingDelay, [
                    (int)$result[1] * 1000, // delay
                    (int)floor(microtime(true) * 1000) // start
                ]),
                default => null,
            };
        }
        exit("fibers queue empty. exit...\n");
    }

    static public function suspend(Wait $type, mixed $payload = null): mixed
    {
        $args = $payload ? [$type, $payload] : [$type];
        return Fiber::suspend($args);
    }

    static public function fork(callable $cb)
    {
        return Fiber::suspend($cb);
    }

    protected function wait(Fiber $fiber, &$wait, $payload, $id = null): void
    {
        $id = $id ?? count($wait);
        $wait[$id] ??= [$payload, []];
        $wait[$id][1][] = $fiber;
    }

    /**
     * Добавляет в очередь файберы, которые ожидают операций I/O
     */
    protected function ioPoll(): void
    {
        /** @phpstan-ignore-next-line */
        while (true) {
            Scheduler::suspend(Wait::ASAP);

            if (!$this->waitingRead && !$this->waitingWrite) {
                continue;
            }

            $rSocks = array_map(fn($x) => $x[0], $this->waitingRead);
            $wSocks = array_map(fn($x) => $x[0], $this->waitingWrite);
            $eSocks = [];

            $timeout = $this->fibers->isEmpty() ? null : 0;
            if (!stream_select($rSocks, $wSocks, $eSocks, $timeout)) {
                continue;
            }

            foreach ($rSocks as $socket) {
                array_map(fn($fiber) => $this->fibers->enqueue($fiber), $this->waitingRead[(int) $socket][1]);
                unset($this->waitingRead[(int) $socket]);
            }

            foreach ($wSocks as $socket) {
                array_map(fn($fiber) => $this->fibers->enqueue($fiber), $this->waitingWrite[(int) $socket][1]);
                unset($this->waitingWrite[(int) $socket]);
            }
        }
    }

    protected function timerPoll(): void
    {
        /** @phpstan-ignore-next-line */
        while (true) {
            Scheduler::suspend(Wait::ASAP);
            if (!$this->waitingDelay) {
                continue;
            }
            $now = (int)floor(microtime(true) * 1000);
            foreach ($this->waitingDelay as $i => $delay) {
                [$timeout, $ts] = $delay[0];
                if ($now > ($ts + $timeout)) {
                    $this->fibers->enqueue($delay[1][0]);
                    unset($this->waitingDelay[$i]);
                }
            }
        }
    }
}