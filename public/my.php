<?php

/**
 * Печатает длительность 1 итерации в миллисекундах с частотой $perSecond
 */
function loopDebug($perSecond = 10)
{
    $skipCount = 0;
    $prevIteration = hrtime(true);
    $ms = floor(microtime(true) * 1000);

    while (true) {
        Fiber::suspend();
        $currentMs = floor(microtime(true) * 1000);
        if (!$skipCount || ($currentMs - $ms) < (1000 / $perSecond)) {
            $skipCount++;
            continue;
        }
        $currentIteration = hrtime(true);
        $ns = ($currentIteration - $prevIteration) / $skipCount;

        echo $ns / 1_000_000 . " ms\n";

        $ms = $currentMs;
        $prevIteration = $currentIteration;
        $skipCount = 0;
    }
}

/**
 * http server
 */
function httpServer($port = 8000)
{
    echo "Starting server at port $port...\n";

    $socket = stream_socket_server("tcp://localhost:$port");
    stream_set_blocking($socket, 0);

    while (true) {
        Fiber::suspend();

//        $clientSocket = stream_socket_accept($socket, 0);
        $clientSocket = stream_socket_accept($socket, 100);

        $data = fread($clientSocket, 8192);

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

        fwrite($clientSocket, $response);
        fclose($clientSocket);
    }
}

$fibers = new SplQueue();

$cbs = [
    loopDebug(...),
    httpServer(...),
];

foreach ($cbs as $cb) {
    $fiber = new Fiber($cb);
    $fiber->start();
    $fibers->enqueue($fiber);
}

while (true) {
    usleep(0);

    if ($fibers->isEmpty()) {
        exit("fibers queue empty. exit...\n");
    }

    $fiber = $fibers->dequeue();
    if ($fiber->isSuspended()) {
        $fiber->resume();
    }
    if (!$fiber->isTerminated()) {
        $fibers->enqueue($fiber);
    }
}