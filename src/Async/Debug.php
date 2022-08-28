<?php

namespace Snidget\Async;

use Snidget\Module\PrintFormat;
use Fiber;
use Snidget\Enum\Wait;

class Debug
{
    protected int $start = 0;
    protected array $fiberTicks = [];
    protected array $impactTime = [];
    protected int $startFiberNs;
    protected string $currentFiberId;

    public function __construct(
        protected int $perSecond = 1
    ){
        $this->start = hrtime(true);
    }

    public function print(): void
    {
        $skipCount = 0;
        $prevIteration = hrtime(true);
        $ms = floor(microtime(true) * 1000);

        while (true) {
            Scheduler::suspend(Wait::ASAP);
            $currentMs = floor(microtime(true) * 1000);
            if (!$skipCount || ($currentMs - $ms) < (1000 / $this->perSecond)) {
                $skipCount++;
                continue;
            }
            $currentIteration = hrtime(true);
            $ns = ($currentIteration - $prevIteration) / $skipCount;
            $ms = $currentMs;
            $prevIteration = $currentIteration;
            $skipCount = 0;

            echo sprintf("Event-loop iteration: %s ms\n", round($ns / 1_000_000, 2));

            $totalTime = (int)round((hrtime(true) - $this->start) / 1_000_000);
            $fibersTime = (int)round(array_sum($this->impactTime) / 1_000_000);

            echo sprintf(
                "FIBERS> all: %s, time: %s ms (%s%% of total %s)\n",
                count($this->fiberTicks),
                PrintFormat::millisecondPrint($fibersTime),
                round($fibersTime / ($totalTime / 100)),
                PrintFormat::millisecondPrint($totalTime),
            );
        }
    }

    public function beforeFiber(int $tsNs, Fiber $fiber): void
    {
        $fiberId = spl_object_hash($fiber);
        if (!$fiber->isStarted()) {
            echo sprintf("FIBERS> start %s\n", $fiberId);
            $this->fiberTicks[$fiberId] = 0;
        }
        $this->fiberTicks[$fiberId]++;

        $this->startFiberNs = $tsNs;
        $this->currentFiberId = $fiberId;
        $this->impactTime[$fiberId] ??= 0;
    }

    public function afterFiber(int $finishFiberNs, Fiber $fiber): void
    {
        $fiberId = spl_object_hash($fiber);

        // TODO: сбрасывать в лог информацию по файберам (список, состояние)
        // в админке по вкладке async показывать как таймлайн
        if ($fiber->isTerminated()) {
            echo sprintf("FIBERS> terminate %s\n", $fiberId);
        }
        $this->impactTime[$this->currentFiberId] += $finishFiberNs - $this->startFiberNs;
    }
}