<?php

namespace App\Module\Async;

use Fiber;
use SplQueue;

class Scheduler
{
    public SplQueue $fibers;

    protected ?Debug $debug = null;

    protected array $waitingRead = [];

    protected array $waitingWrite = [];

    // служебные файберы (ioPoll, Debug) крутятся в очереди всегда
    protected int $serviceFibers = 1;

    public function __construct(array $cbs, ?Debug $debug = null)
    {
        if ($debug instanceof Debug) {
            $this->debug = $debug;
            $cbs[] = $debug->print(...);
            $this->serviceFibers++;
        }

        $cbs[] = $this->ioPoll(...);

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
                Wait::READ  => $this->wait($fiber, $this->waitingRead, $result[1]),
                Wait::WRITE => $this->wait($fiber, $this->waitingWrite, $result[1]),
                default => null,
            };
        }

        exit("fibers queue empty. exit...\n");
    }

    protected function execute(Fiber $fiber): mixed
    {
        if ($this->debug instanceof Debug) {
            $this->debug->beforeFiber(hrtime(true), $fiber);
        }

        if ($fiber->isStarted()) {
            $result = $fiber->isSuspended() ? $fiber->resume() : null;
        } else {
            $result = $fiber->start();
        }

        if ($this->debug instanceof Debug) {
            $this->debug->afterFiber(hrtime(true), $fiber);
        }

        return $result;
    }

    public static function suspend(Wait $type, mixed $payload = null): mixed
    {
        $args = $payload ? [$type, $payload] : [$type];
        return Fiber::suspend($args);
    }

    public static function fork(callable $cb): mixed
    {
        return Fiber::suspend($cb);
    }

    /**
     * @param resource $socket
     */
    protected function wait(Fiber $fiber, array &$wait, mixed $socket): void
    {
        $wait[(int)$socket] ??= [$socket, []];
        $wait[(int)$socket][1][] = $fiber;
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

            $rSocks = array_map(fn(array $x) => $x[0], $this->waitingRead);
            $wSocks = array_map(fn(array $x) => $x[0], $this->waitingWrite);
            $eSocks = [];

            // в очереди только служебные файберы: ждём I/O, а не крутим цикл вхолостую
            $isIdle = count($this->fibers) < $this->serviceFibers;
            if (!stream_select($rSocks, $wSocks, $eSocks, $isIdle ? 1 : 0)) {
                continue;
            }

            foreach ($rSocks as $socket) {
                array_map($this->fibers->enqueue(...), $this->waitingRead[(int) $socket][1]);
                unset($this->waitingRead[(int) $socket]);
            }

            foreach ($wSocks as $socket) {
                array_map($this->fibers->enqueue(...), $this->waitingWrite[(int) $socket][1]);
                unset($this->waitingWrite[(int) $socket]);
            }
        }
    }
}
