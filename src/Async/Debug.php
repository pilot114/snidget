<?php

namespace Snidget\Async;

use SplQueue;
use Fiber;
use Snidget\Enum\Wait;

class Debug
{
    protected int $start = 0;

    protected array $fiberIds = [];
    protected array $impactPersent = [];

    protected int $startFiberTs;
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

        /** @phpstan-ignore-next-line */
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

            $totalTime = round((hrtime(true) - $this->start) / 1_000_000);
            $fibersTime = round(array_sum($this->impactPersent) / 1_000_000);

            echo sprintf(
                "FIBERS> count: %s, time: %s ms (%s%% of total %s ms)\n",
                count($this->fiberIds),
                $fibersTime,
                round($fibersTime / ($totalTime / 100)),
                $totalTime,
            );
        }
    }

    public function beforeFiber(int $tsNs, SplQueue $fibers, Fiber $fiber): void
    {
        $fiberId = spl_object_hash($fiber);
        $this->fiberIds = [ $fiberId ];
        foreach ($fibers as $fiber) {
            $this->fiberIds[] = spl_object_hash($fiber);
        }

        $this->startFiberTs = $tsNs;
        $this->currentFiberId = $fiberId;
        if (!isset($this->impactPersent[$fiberId])) {
            $this->impactPersent[$fiberId] = 0;
        }
    }

    public function afterFiber(int $tsNs): void
    {
        $this->impactPersent[$this->currentFiberId] += $tsNs - $this->startFiberTs;
    }
}