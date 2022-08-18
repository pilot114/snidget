<?php

class CoroutineValueWrapper {
    public function __construct(
        protected $value
    ) {}

    public function getValue()
    {
        return $this->value;
    }
}

class CoroutineReturnValue extends CoroutineValueWrapper { }
class CoroutinePlainValue extends CoroutineValueWrapper { }

function retval($value) {
    return new CoroutineReturnValue($value);
}

function plainval($value) {
    return new CoroutinePlainValue($value);
}

function stackedCoroutine(Generator $gen): Generator
{
    $stack = new SplStack;
    $exception = null;

    while (true) {
        try {
            if ($exception) {
                $gen->throw($exception);
                $exception = null;
                continue;
            }

            $value = $gen->current();

            if ($value instanceof Generator) {
                $stack->push($gen);
                $gen = $value;
                continue;
            }

            $isReturnValue = $value instanceof CoroutineReturnValue;
            if (!$gen->valid() || $isReturnValue) {
                if ($stack->isEmpty()) {
                    return;
                }

                $gen = $stack->pop();
                $gen->send($isReturnValue ? $value->getValue() : NULL);
                continue;
            }

            if ($value instanceof CoroutinePlainValue) {
                $value = $value->getValue();
            }

            try {
                $sendValue = (yield $gen->key() => $value);
            } catch (Exception $e) {
                $gen->throw($e);
                continue;
            }

            $gen->send($sendValue);
        } catch (Exception $e) {
            if ($stack->isEmpty()) {
                throw $e;
            }

            $gen = $stack->pop();
            $exception = $e;
        }
    }
}

class Task
{
    protected int $taskId;
    protected Generator $coroutine;
    protected mixed $sendValue = null;
    protected bool $beforeFirstYield = true;
    protected ?Throwable $exception = null;

    public function __construct($taskId, Generator $coroutine) {
        $this->taskId = $taskId;
        $this->coroutine = stackedCoroutine($coroutine);
    }

    public function getTaskId() {
        return $this->taskId;
    }

    public function setSendValue($sendValue) {
        $this->sendValue = $sendValue;
    }

    public function setException($exception) {
        $this->exception = $exception;
    }

    public function run()
    {
        if ($this->beforeFirstYield) {
            $this->beforeFirstYield = false;
            return $this->coroutine->current();
        }
        if ($this->exception) {
            $retval = $this->coroutine->throw($this->exception);
            $this->exception = null;
            return $retval;
        }
        $retval = $this->coroutine->send($this->sendValue);
        $this->sendValue = null;
        return $retval;
    }

    public function isFinished() {
        return !$this->coroutine->valid();
    }
}

class Scheduler {
    protected $maxTaskId = 0;
    protected $taskMap = []; // taskId => task
    protected $taskQueue;

    // resourceID => [socket, tasks]
    protected $waitingForRead = [];
    protected $waitingForWrite = [];

    public function __construct() {
        $this->taskQueue = new SplQueue();
    }

    public function newTask(Generator $coroutine) {
        $tid = ++$this->maxTaskId;
        $task = new Task($tid, $coroutine);
        $this->taskMap[$tid] = $task;
        $this->schedule($task);
        return $tid;
    }

    public function schedule(Task $task) {
        $this->taskQueue->enqueue($task);
    }

    public function killTask($tid) {
        if (!isset($this->taskMap[$tid])) {
            return false;
        }

        unset($this->taskMap[$tid]);

        foreach ($this->taskQueue as $i => $task) {
            if ($task->getTaskId() === $tid) {
                unset($this->taskQueue[$i]);
                break;
            }
        }

        return true;
    }

    public function run() {
        $this->newTask($this->ioPollTask());

        while (!$this->taskQueue->isEmpty()) {
            $task = $this->taskQueue->dequeue();
            $retval = $task->run();

            if ($retval instanceof SystemCall) {
                try {
                    $retval($task, $this);
                } catch (Exception $e) {
                    $task->setException($e);
                    $this->schedule($task);
                }
                continue;
            }

            if ($task->isFinished()) {
                unset($this->taskMap[$task->getTaskId()]);
            } else {
                $this->schedule($task);
            }
        }
    }

    protected function ioPoll($timeout) {
        if (empty($this->waitingForRead) && empty($this->waitingForWrite)) {
            return;
        }

        $rSocks = [];
        foreach ($this->waitingForRead as list($socket)) {
            $rSocks[] = $socket;
        }

        $wSocks = [];
        foreach ($this->waitingForWrite as list($socket)) {
            $wSocks[] = $socket;
        }

        $eSocks = []; // dummy

        if (!stream_select($rSocks, $wSocks, $eSocks, $timeout)) {
            return;
        }

        foreach ($rSocks as $socket) {
            list(, $tasks) = $this->waitingForRead[(int) $socket];
            unset($this->waitingForRead[(int) $socket]);

            foreach ($tasks as $task) {
                $this->schedule($task);
            }
        }

        foreach ($wSocks as $socket) {
            list(, $tasks) = $this->waitingForWrite[(int) $socket];
            unset($this->waitingForWrite[(int) $socket]);

            foreach ($tasks as $task) {
                $this->schedule($task);
            }
        }
    }

    protected function ioPollTask() {
        while (true) {
            if ($this->taskQueue->isEmpty()) {
                $this->ioPoll(null);
            } else {
                $this->ioPoll(0);
            }
            yield;
        }
    }

    public function waitForRead($socket, Task $task) {
        if (isset($this->waitingForRead[(int) $socket])) {
            $this->waitingForRead[(int) $socket][1][] = $task;
        } else {
            $this->waitingForRead[(int) $socket] = [$socket, [$task]];
        }
    }

    public function waitForWrite($socket, Task $task) {
        if (isset($this->waitingForWrite[(int) $socket])) {
            $this->waitingForWrite[(int) $socket][1][] = $task;
        } else {
            $this->waitingForWrite[(int) $socket] = [$socket, [$task]];
        }
    }
}

class SystemCall {
    protected $callback;

    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function __invoke(Task $task, Scheduler $scheduler)
    {
        $callback = $this->callback; // Yes, PHP sucks
        return $callback($task, $scheduler);
    }

    static function getTaskId(): self
    {
        return new self(function(Task $task, Scheduler $scheduler) {
            $task->setSendValue($task->getTaskId());
            $scheduler->schedule($task);
        });
    }

    static function newTask(Generator $coroutine): self
    {
        return new self(
            function(Task $task, Scheduler $scheduler) use ($coroutine) {
                $task->setSendValue($scheduler->newTask($coroutine));
                $scheduler->schedule($task);
            }
        );
    }

    static function killTask($tid): self
    {
        return new self(
            function(Task $task, Scheduler $scheduler) use ($tid) {
                if ($scheduler->killTask($tid)) {
                    $scheduler->schedule($task);
                } else {
                    throw new InvalidArgumentException('Invalid task ID!');
                }
            }
        );
    }

    static function waitForRead($socket): self
    {
        return new self(
            function(Task $task, Scheduler $scheduler) use ($socket) {
                $scheduler->waitForRead($socket, $task);
            }
        );
    }

    static function waitForWrite($socket): self
    {
        return new self(
            function(Task $task, Scheduler $scheduler) use ($socket) {
                $scheduler->waitForWrite($socket, $task);
            }
        );
    }
}

// ----------------------------------------//

function server($port) {
    echo "Starting server at port $port...\n";

    $socket = @stream_socket_server("tcp://localhost:$port", $errNo, $errStr);
    if (!$socket) {
        throw new Exception($errStr, $errNo);
    }

    stream_set_blocking($socket, 0);

    while (true) {
        yield SystemCall::waitForRead($socket);
        $clientSocket = stream_socket_accept($socket, 0);
        yield SystemCall::newTask(handleClient($clientSocket));
    }
}

function handleClient($socket) {
    yield SystemCall::waitForRead($socket);
    $data = fread($socket, 8192);

    $msg = "Received following request:\n\n$data";
    $msgLength = strlen($msg);

    $response = <<<RES
HTTP/1.1 200 OK\r
Content-Type: text/plain\r
Content-Length: $msgLength\r
Connection: close\r
\r
$msg
RES;

    yield SystemCall::waitForWrite($socket);
    fwrite($socket, $response);

    fclose($socket);
}

$scheduler = new Scheduler;
$scheduler->newTask(server(8000));
$scheduler->run();