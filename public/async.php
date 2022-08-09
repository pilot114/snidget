<?php

// https://github.com/reactphp/event-loop/blob/1.x/src/StreamSelectLoop.php
// Единственная реализация event-loop в php из коробки

final class FutureTickQueue
{
    public function __construct(
        private $queue = new SplQueue()
    ){}

    public function add(callable $listener)
    {
        $this->queue->enqueue($listener);
    }

    public function tick()
    {
        $count = $this->queue->count();
        while ($count--) {
            \call_user_func(
                $this->queue->dequeue()
            );
        }
    }

    public function isEmpty()
    {
        return $this->queue->isEmpty();
    }
}

final class Timer
{
    const MIN_INTERVAL = 0.000001;

    public function __construct(
        private float $interval,
        private $callback,
        private bool $periodic = false
    )
    {
        if ($interval < self::MIN_INTERVAL) {
            $interval = self::MIN_INTERVAL;
            $this->interval = $interval;
        }
    }

    public function getInterval()
    {
        return $this->interval;
    }

    public function getCallback()
    {
        return $this->callback;
    }

    public function isPeriodic()
    {
        return $this->periodic;
    }
}

final class Timers
{
    private $time;
    private $timers = [];
    private $schedule = [];
    private $sorted = true;
    private $useHighResolution;

    public function __construct()
    {
        $this->useHighResolution = \function_exists('hrtime');
    }

    public function updateTime()
    {
        return $this->time = $this->useHighResolution ? \hrtime(true) * 1e-9 : \microtime(true);
    }

    public function getTime()
    {
        return $this->time ?: $this->updateTime();
    }

    public function add($timer): void
    {
        $id = \spl_object_hash($timer);
        $this->timers[$id] = $timer;
        $this->schedule[$id] = $timer->getInterval() + $this->updateTime();
        $this->sorted = false;
    }

    public function contains($timer): bool
    {
        return isset($this->timers[\spl_object_hash($timer)]);
    }

    public function cancel($timer): void
    {
        $id = \spl_object_hash($timer);
        unset($this->timers[$id], $this->schedule[$id]);
    }

    public function getFirst()
    {
        if (!$this->sorted) {
            $this->sorted = true;
            \asort($this->schedule);
        }

        return \reset($this->schedule);
    }

    public function isEmpty()
    {
        return \count($this->timers) === 0;
    }

    public function tick()
    {
        if (!$this->schedule) {
            return;
        }

        if (!$this->sorted) {
            $this->sorted = true;
            \asort($this->schedule);
        }

        $time = $this->updateTime();

        foreach ($this->schedule as $id => $scheduled) {
            if ($scheduled >= $time) {
                break;
            }

            if (!isset($this->schedule[$id]) || $this->schedule[$id] !== $scheduled) {
                continue;
            }

            $timer = $this->timers[$id];
            \call_user_func($timer->getCallback(), $timer);

            if ($timer->isPeriodic() && isset($this->timers[$id])) {
                $this->schedule[$id] = $timer->getInterval() + $time;
                $this->sorted = false;
            } else {
                unset($this->timers[$id], $this->schedule[$id]);
            }
        }
    }
}

final class StreamSelectLoop
{
    const MICROSECONDS_PER_SECOND = 1000000;

    private $futureTickQueue;
    private $timers;
    private $readStreams = [];
    private $readListeners = [];
    private $writeStreams = [];
    private $writeListeners = [];
    private $running;

    public function __construct()
    {
        $this->futureTickQueue = new FutureTickQueue();
        $this->timers = new Timers();
    }

    public function addReadStream($stream, $listener): void
    {
        $key = (int) $stream;

        if (!isset($this->readStreams[$key])) {
            $this->readStreams[$key] = $stream;
            $this->readListeners[$key] = $listener;
        }
    }

    public function addWriteStream($stream, $listener): void
    {
        $key = (int) $stream;

        if (!isset($this->writeStreams[$key])) {
            $this->writeStreams[$key] = $stream;
            $this->writeListeners[$key] = $listener;
        }
    }

    public function removeReadStream($stream): void
    {
        $key = (int) $stream;

        unset(
            $this->readStreams[$key],
            $this->readListeners[$key]
        );
    }

    public function removeWriteStream($stream): void
    {
        $key = (int) $stream;

        unset(
            $this->writeStreams[$key],
            $this->writeListeners[$key]
        );
    }

    public function addTimer($interval, $callback): Timer
    {
        $timer = new Timer($interval, $callback, false);
        $this->timers->add($timer);
        return $timer;
    }

    public function addPeriodicTimer($interval, $callback): Timer
    {
        $timer = new Timer($interval, $callback, true);
        $this->timers->add($timer);
        return $timer;
    }

    public function cancelTimer($timer): void
    {
        $this->timers->cancel($timer);
    }

    public function futureTick($listener): void
    {
        $this->futureTickQueue->add($listener);
    }

    public function run(): void
    {
        $this->running = true;

        while ($this->running) {
            $this->futureTickQueue->tick();

            $this->timers->tick();

            if (!$this->running || !$this->futureTickQueue->isEmpty()) {
                $timeout = 0;
            } elseif ($scheduledAt = $this->timers->getFirst()) {
                $timeout = $scheduledAt - $this->timers->getTime();
                if ($timeout < 0) {
                    $timeout = 0;
                } else {
                    $timeout *= self::MICROSECONDS_PER_SECOND;
                    $timeout = $timeout > \PHP_INT_MAX ? \PHP_INT_MAX : (int)$timeout;
                }
            } elseif ($this->readStreams || $this->writeStreams) {
                $timeout = null;
            } else {
                break;
            }

            $this->waitForStreamActivity($timeout);
        }
    }

    public function stop(): void
    {
        $this->running = false;
    }

    private function waitForStreamActivity(?int $timeout): void
    {
        $read  = $this->readStreams;
        $write = $this->writeStreams;

        $available = $this->streamSelect($read, $write, $timeout);
        if (false === $available) {
            return;
        }

        foreach ($read as $stream) {
            $key = (int) $stream;

            if (isset($this->readListeners[$key])) {
                \call_user_func($this->readListeners[$key], $stream);
            }
        }

        foreach ($write as $stream) {
            $key = (int) $stream;

            if (isset($this->writeListeners[$key])) {
                \call_user_func($this->writeListeners[$key], $stream);
            }
        }
    }

    private function streamSelect(array &$read, array &$write, ?int $timeout)
    {
        if ($read || $write) {
            $except = null;
            if (\DIRECTORY_SEPARATOR === '\\') {
                $except = array();
                foreach ($write as $key => $socket) {
                    if (!isset($read[$key]) && @\ftell($socket) === 0) {
                        $except[$key] = $socket;
                    }
                }
            }

            $previous = \set_error_handler(function ($errno, $errstr) use (&$previous) {
                $eintr = \defined('SOCKET_EINTR') ? \SOCKET_EINTR : 4;
                if ($errno === \E_WARNING && \strpos($errstr, '[' . $eintr .']: ') !== false) {
                    return;
                }
                return ($previous !== null) ? \call_user_func_array($previous, \func_get_args()) : false;
            });

            try {
                $ret = \stream_select($read, $write, $except, $timeout === null ? null : 0, $timeout);
                \restore_error_handler();
            } catch (\Throwable $e) {
                \restore_error_handler();
                throw $e;
            } catch (\Exception $e) {
                \restore_error_handler();
                throw $e;
            }

            if ($except) {
                $write = \array_merge($write, $except);
            }
            return $ret;
        }

        if ($timeout > 0) {
            \usleep($timeout);
        } elseif ($timeout === null) {
            \sleep(PHP_INT_MAX);
        }

        return 0;
    }
}

$loop = new StreamSelectLoop();

// pair

[$reader, $writer] = createSocketPair();
$timeout = $loop->addTimer(0.1, fn() => $loop->removeReadStream($reader));

$loop->addReadStream($reader, function () use ($loop, $reader, $timeout) {
    echo fread($reader, 1024);
    $loop->removeReadStream($reader);
    $loop->cancelTimer($timeout);
});

fwrite($writer, "foo\n");

// server
$server = stream_socket_server('127.0.0.1:0');

$errno = $errstr = null;
$connecting = stream_socket_client(
    stream_socket_get_name($server, false),
    $errno,
    $errstr,
    0,
    STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
);

$timeout = $loop->addTimer(0.1, fn() => $loop->removeWriteStream($connecting));

$loop->addWriteStream($connecting, function () use ($loop, $connecting, $timeout) {
    $loop->removeWriteStream($connecting);
    $loop->cancelTimer($timeout);
});

$loop->run();

function tickLoop($loop)
{
    $loop->futureTick(fn() => $loop->stop());
    $loop->run();
}

function createSocketPair(): array
{
    [$reader, $writer] = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
    stream_set_read_buffer($reader, 0);
    stream_set_read_buffer($writer, 0);

    return [$reader, $writer];
}