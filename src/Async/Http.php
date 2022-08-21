<?php

namespace Snidget\Async;

use Snidget\Enum\Wait;

class Http
{
    /**
     * http server
     * current stable performance: ab -n 100000 -c 40 localhost:8000/
     * RPS ~ 1650, median - 20 ms
     * TODO: without usleep: RPS ~ 12600, median - 3 ms
     */
    static public function server($port = 8000): void
    {
        echo "Starting server at port $port...\n";

        $socket = stream_socket_server("tcp://localhost:$port");
        stream_set_blocking($socket, false);

        /** @phpstan-ignore-next-line */
        while (true) {
            Scheduler::suspend(Wait::READ, $socket);
            $clientSocket = stream_socket_accept($socket, 0);

            Scheduler::fork(function() use ($clientSocket) {
                Scheduler::suspend(Wait::READ, $clientSocket);
                $request = fread($clientSocket, 8192);
                $response = self::handle($request);

                Scheduler::suspend(Wait::WRITE, $clientSocket);
                fwrite($clientSocket, $response);
                fclose($clientSocket);
            });
        }
    }

    static protected function handle(string $request): string
    {
        $start = hrtime(true);
//    $msg = "Received following request:\n\n$request";
        $msg = "my.php_" . strlen($request);
        $msgLength = strlen($msg);
        // https://web.dev/custom-metrics/?utm_source=devtools#server-timing-api
        $duration = round((hrtime(true) - $start) / 1_000_000, 2);
        return <<<RES
HTTP/1.1 200 OK\r
Content-Type: text/plain\r
Content-Length: $msgLength\r
Connection: close\r
Server-Timing: miss, app;dur=$duration\r
\r
$msg
RES;
    }
}