<?php

enum WAIT
{
    case ASAP;  // empty payload
    case WRITE; // payload - socket
    case READ;  // payload - socket
    case DELAY; // payload - seconds (float)
}

function suspend(WAIT $type, $payload = null): mixed
{
    $args = $payload ? [$type, $payload] : [$type];
    return Fiber::suspend($args);
}

class Scheduler
{
    protected SplQueue $fibers;
    protected array $waitingForRead = [];
    protected array $waitingForWrite = [];

    public function __construct(array $cbs)
    {
        $cbs[] = $this->debug(...);
        $cbs[] = $this->ioPoll(...);

        $this->fibers = new SplQueue();
        foreach ($cbs as $cb) {
            $fiber = new Fiber($cb);
            $this->fibers->enqueue($fiber);
        }
    }

    public function run(): void
    {
        while (!$this->fibers->isEmpty()) {
            usleep(0);
            $fiber = $this->fibers->dequeue();

            if ($fiber->isTerminated()) {
                continue;
            }
            if ($fiber->isStarted()) {
                $result = $fiber->isSuspended() ? $fiber->resume() : null;
            } else {
                $result = $fiber->start();
            }
            if (!is_array($result)) {
                continue;
            }
            $type = $result[0];

            match ($type) {
                WAIT::ASAP  => $this->fibers->enqueue($fiber),
                WAIT::READ  => $this->waitForRead($result[1], $fiber),
                WAIT::WRITE => $this->waitForWrite($result[1], $fiber),
            };
        }
        exit("fibers queue empty. exit...\n");
    }

    protected function waitForRead($socket, $fiber)
    {
        $socketId = (int) $socket;
        if (!isset($this->waitingForRead[$socketId])) {
            $this->waitingForRead[$socketId] = [$socket, []];
        }
        $this->waitingForRead[$socketId][1][] = $fiber;
    }

    protected function waitForWrite($socket, $fiber)
    {
        $socketId = (int) $socket;
        if (!isset($this->waitingForWrite[$socketId])) {
            $this->waitingForWrite[$socketId] = [$socket, []];
        }
        $this->waitingForWrite[$socketId][1][] = $fiber;
    }

    /**
     * Запускает файберы, которые ожидают операций I/O
     */
    protected function ioPoll()
    {
        while (true) {
            suspend(WAIT::ASAP);

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
                array_map(
                    fn($x) => $this->fibers->enqueue($x),
                    $this->waitingForRead[(int) $socket][1]
                );
                unset($this->waitingForRead[(int) $socket]);
            }

            foreach ($wSocks as $socket) {
                array_map(
                    fn($x) => $this->fibers->enqueue($x),
                    $this->waitingForWrite[(int) $socket][1]
                );
                unset($this->waitingForWrite[(int) $socket]);
            }
        }
    }

    /**
     * Печатает длительность 1 итерации в миллисекундах с частотой $perSecond
     * TODO: для каждого файбера выводить время исполнения + таймстампы переключения между файберами
     */
    protected function debug($perSecond = 10)
    {
        $skipCount = 0;
        $prevIteration = hrtime(true);
        $ms = floor(microtime(true) * 1000);

        while (true) {
            suspend(WAIT::ASAP);
            $currentMs = floor(microtime(true) * 1000);
            if (!$skipCount || ($currentMs - $ms) < (1000 / $perSecond)) {
                $skipCount++;
                continue;
            }
            $currentIteration = hrtime(true);
            $ns = ($currentIteration - $prevIteration) / $skipCount;

            echo round($ns / 1_000_000, 2) . " ms\n";

            $ms = $currentMs;
            $prevIteration = $currentIteration;
            $skipCount = 0;
        }
    }
}

/**
 * http server
 */
function httpServer($port = 8000)
{
    echo "Starting server at port $port...\n";

    $socket = stream_socket_server("tcp://localhost:$port");
    stream_set_blocking($socket, false);

    while (true) {
        suspend(WAIT::READ, $socket);
        $clientSocket = stream_socket_accept($socket, 0);

        // TODO: это можно обрабатывать в отдельном файбере
        suspend(WAIT::READ, $clientSocket);
        $request = fread($clientSocket, 8192);
        $response = httpHandler($request);

        suspend(WAIT::WRITE, $clientSocket);
        fwrite($clientSocket, $response);
        fclose($clientSocket);
    }
}

function httpHandler(string $request): string
{
    $msg = "Received following request:\n\n$request";
    $msgLength = strlen($msg);

    return <<<RES
HTTP/1.1 200 OK\r
Content-Type: text/plain\r
Content-Length: $msgLength\r
Connection: close\r
\r
$msg
RES;
}

$scheduler = new Scheduler([
    httpServer(...),
]);
$scheduler->run();