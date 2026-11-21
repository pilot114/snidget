<?php

namespace App\Module\Async;

use Snidget\HTTP\Request;
use Snidget\HTTP\Response;
use Snidget\Kernel\SnidgetException;
use Throwable;

class Server
{
    private const string HOST = '0.0.0.0';

    private const int PORT = 80;

    /** @var \Closure(Request): Response */
    public static \Closure $kernelHandler;

    /** @var array<string> пути статики относительно корня */
    protected static array $serveFiles = [];

    /**
     * http server
     * benchmark: ab -n 100000 -c 40 127.0.0.1:8000/
     */
    public static function http(): never
    {
        $pwd = $_SERVER['PWD'] ?? getcwd();
        if (!is_string($pwd)) {
            throw new SnidgetException('Не удалось определить рабочую директорию');
        }

        self::$serveFiles = str_replace($pwd . '/', '', self::getPublicFiles($pwd));

        echo sprintf("Starting server at http://%s:%s, serve %s\n", self::HOST, self::PORT, $pwd);
        $address = sprintf('tcp://%s:%s', self::HOST, self::PORT);
        $socket = stream_socket_server($address);
        if ($socket === false) {
            throw new SnidgetException("Не удалось создать сокет с адресом $address");
        }

        stream_set_blocking($socket, false);

        while (true) {
            Scheduler::suspend(Wait::READ, $socket);
            $clientSocket = stream_socket_accept($socket, 0);
            if ($clientSocket === false) {
                // клиент мог отвалиться между select и accept — это не повод останавливать сервер
                continue;
            }

            Scheduler::fork(function () use ($clientSocket): void {
                // a request can come in several packets
                $request = '';
                do {
                    Scheduler::suspend(Wait::READ, $clientSocket);
                    $chunk = (string)fread($clientSocket, 8192);
                    $request .= $chunk;
                } while ($chunk !== '' && !self::isComplete($request));

                if ($request === '') {
                    fclose($clientSocket);
                    return;
                }

                // ошибка одного запроса не должна ронять весь сервер
                try {
                    $response = self::httpHandle($request);
                } catch (Throwable $throwable) {
                    error_log((string)$throwable);
                    $response = self::format(new Response('Internal Server Error', 500), 0);
                }

                Scheduler::suspend(Wait::WRITE, $clientSocket);
                fwrite($clientSocket, $response);
                fclose($clientSocket);
            });
        }
    }

    /**
     * Headers are over and the body has Content-Length bytes
     */
    public static function isComplete(string $request): bool
    {
        $headersEnd = strpos($request, "\r\n\r\n");
        if ($headersEnd === false) {
            return false;
        }

        preg_match('/^Content-Length:\s*(\d+)/mi', substr($request, 0, $headersEnd), $match);
        return strlen($request) - $headersEnd - 4 >= (int)($match[1] ?? 0);
    }

    /** @return array<string> */
    protected static function getPublicFiles(string $base): array
    {
        $files = glob($base . '*');
        $files = $files === false ? [] : $files;

        $dirs = glob($base . '*', GLOB_ONLYDIR | GLOB_NOSORT | GLOB_MARK);
        foreach ($dirs === false ? [] : $dirs as $dir) {
            $dirFiles = self::getPublicFiles($dir);
            $files = array_merge($files, $dirFiles);
        }

        return array_filter($files, is_file(...));
    }

    protected static function httpHandle(string $request): string
    {
        $start = hrtime(true);
        // новый объект на каждый запрос: иначе заголовки и тело переходят к следующему клиенту
        $request = new Request()->fromString($request, $start);

        // static and separate php scripts
        if (in_array($request->uri, self::$serveFiles, true)) {
            if (str_ends_with($request->uri, '.php')) {
                // TODO: handle output and terminate in current process
                $response = new Response((string)shell_exec('php ' . $request->uri));
            } else {
                $response = new Response((string)file_get_contents($request->uri));
            }
        } else {
            $response = (self::$kernelHandler)($request);
        }

        // https://web.dev/custom-metrics/?utm_source=devtools#server-timing-api
        return self::format($response, round((hrtime(true) - $start) / 1_000_000, 2));
    }

    protected static function format(Response $response, float $duration): string
    {
        $body = $response->getBody();
        $headers = $response->getHeaders();
        if (!isset($headers['Content-Type'])) {
            json_decode($body);
            $isJson = $body !== '' && json_last_error() === JSON_ERROR_NONE;
            $headers['Content-Type'] = ($isJson ? 'application/json' : 'text/html') . '; charset=utf-8';
        }

        $headers += [
            'Content-Length' => strlen($body),
            'Connection' => 'close',
            'Server-Timing' => "miss, app;dur=$duration",
        ];

        $head = "HTTP/1.1 {$response->getStatusCode()} \r\n";
        foreach ($headers as $name => $value) {
            $head .= "$name: $value\r\n";
        }

        return "$head\r\n$body";
    }
}
