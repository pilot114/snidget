<?php

namespace Snidget\Async;

use Snidget\Enum\Wait;
use Snidget\Request;

class Server
{
    const HOST = 'localhost';
    const PORT = 8000;

    static public $kernelHandler;
    static public Request $request;

    /**
     * http server
     * current stable performance: ab -n 100000 -c 40 localhost:8000/
     * RPS ~ 1650, median - 20 ms
     * TODO: without usleep in scheduler: RPS ~ 12600, median - 3 ms
     */
    static public function http(): void
    {
        echo sprintf("Starting server at %s:%s...\n", self::HOST, self::PORT);
        $socket = stream_socket_server( sprintf('tcp://%s:%s', self::HOST, self::PORT));
        stream_set_blocking($socket, false);

        /** @phpstan-ignore-next-line */
        while (true) {
            Scheduler::suspend(Wait::READ, $socket);
            $clientSocket = stream_socket_accept($socket, 0);

            Scheduler::fork(function() use ($clientSocket) {
                Scheduler::suspend(Wait::READ, $clientSocket);
                $request = fread($clientSocket, 8192);
                $response = self::httpHandle($request);

                Scheduler::suspend(Wait::WRITE, $clientSocket);
                fwrite($clientSocket, $response);
                fclose($clientSocket);
            });
        }
    }

    static protected function httpHandle(string $request): string
    {
        $start = hrtime(true);

        $responseString = (self::$kernelHandler)(self::$request->buildFromString($request, $start));
        $msgLength = strlen($responseString);

        // https://web.dev/custom-metrics/?utm_source=devtools#server-timing-api
        $duration = round((hrtime(true) - $start) / 1_000_000, 2);

        json_decode($responseString);
        $isJson = json_last_error() === JSON_ERROR_NONE;
        $contentType = $isJson ? 'json' : 'html';

        return <<<RES
HTTP/1.1 200 OK\r
Content-Type: text/$contentType; charset=utf-8\r
Content-Length: $msgLength\r
Connection: close\r
Server-Timing: miss, app;dur=$duration\r
\r
$responseString
RES;
    }
}