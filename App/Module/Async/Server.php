<?php

namespace App\Module\Async;

use Snidget\Enum\Wait;
use Snidget\Exception\SnidgetException;
use Snidget\Request;

class Server
{
    const HOST = '0.0.0.0';
    const PORT = 80;

    public static \Closure $kernelHandler;
    public static Request $request;

    protected static array $serveFiles = [];

    /**
     * http server
     * current stable performance: ab -n 100000 -c 40 127.0.0.1:8000/
     * RPS ~ 1650, median - 20 ms
     * TODO: without usleep in scheduler: RPS ~ 12600, median - 3 ms
     */
    public static function http(): void
    {
        self::$serveFiles = array_map(
            fn($x) => str_replace($_SERVER['PWD'] . '/', '', $x),
            self::getPublicFiles($_SERVER['PWD'])
        );

        echo sprintf("Starting server at http://%s:%s, serve %s\n", self::HOST, self::PORT, $_SERVER['PWD']);
        $address = sprintf('tcp://%s:%s', self::HOST, self::PORT);
        $socket = stream_socket_server($address);
        if (!$socket) {
            throw new SnidgetException("Не удалось создать сокет с адресом $address");
        }

        stream_set_blocking($socket, false);

        while (true) {
            Scheduler::suspend(Wait::READ, $socket);
            $clientSocket = stream_socket_accept($socket, 0);
            if (!$clientSocket) {
                throw new SnidgetException("Не удалось создать клиентский сокет");
            }

            Scheduler::fork(function () use ($clientSocket) {
                Scheduler::suspend(Wait::READ, $clientSocket);
                $request = fread($clientSocket, 8192);
                if (!$request) {
                    throw new SnidgetException("Не удалось прочитать данные из клиентского сокета");
                }
                $response = self::httpHandle($request);

                Scheduler::suspend(Wait::WRITE, $clientSocket);
                fwrite($clientSocket, $response);
                fclose($clientSocket);
            });
        }
    }

    protected static function getPublicFiles(string $base): array
    {
        $files = glob($base . '*') ?: [];
        $dirs = glob($base . '*', GLOB_ONLYDIR | GLOB_NOSORT | GLOB_MARK) ?: [];
        foreach ($dirs as $dir) {
            $dirFiles = self::getPublicFiles($dir);
            $files = array_merge($files, $dirFiles);
        }
        return array_filter($files, fn($x) => is_file($x));
    }

    protected static function httpHandle(string $request): string
    {
        $start = hrtime(true);
        $request = self::$request->fromString($request, $start);

        // static and separate php scripts
        if (in_array($request->uri, self::$serveFiles)) {
            if (str_ends_with($request->uri, '.php')) {
                // TODO: handle output and terminate in current process
                $responseString = shell_exec('php ' . $request->uri);
            } else {
                $responseString = file_get_contents($request->uri);
            }
        } else {
            $responseString = (self::$kernelHandler)($request);
        }

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
