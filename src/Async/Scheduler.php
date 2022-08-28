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
        $cbs[] = $this->delayPoll(...);

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
        pcntl_signal(SIGTERM, function() use (&$isTerminate) {
            $isTerminate = true;
        });

        while (!$this->fibers->isEmpty() && !$isTerminate) {
            usleep(0);
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
                Wait::READ  => $this->wait($fiber, $this->waitingRead, $result[1], (int)$result[1]),
                Wait::WRITE => $this->wait($fiber, $this->waitingWrite, $result[1], (int)$result[1]),
                Wait::DELAY => $this->wait($fiber, $this->waitingDelay, [
                    $result[1] * 1000, // delay
                    floor(microtime(true) * 1000) // start
                ]),
                default => null,
            };
        }
        exit("fibers queue empty. exit...\n");
    }

    protected function execute(Fiber $fiber)
    {
        $this->debug && $this->debug->beforeFiber(hrtime(true), $fiber);
        if ($fiber->isStarted()) {
            $result = $fiber->isSuspended() ? $fiber->resume() : null;
        } else {
            $result = $fiber->start();
        }
        $this->debug && $this->debug->afterFiber(hrtime(true), $fiber);
        return $result;
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

    protected function delayPoll(): void
    {
        while (true) {
            Scheduler::suspend(Wait::ASAP);
            if (!$this->waitingDelay) {
                continue;
            }
            $now = (int)floor(microtime(true) * 1000);
            foreach ($this->waitingDelay as $i => $delay) {
                [$timeout, $ts] = $delay[0];
                if ($now > ((int)$ts + (int)$timeout)) {
                    $this->fibers->enqueue($delay[1][0]);
                    unset($this->waitingDelay[$i]);
                }
            }
        }
    }
}