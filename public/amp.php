<?php

// TODO: для асинхронности нужно:
// - в Request использовать $_SERVER (?) ,заполненный в event-loop
// - не отправлять ответ, в возвращать в event-loop

//include_once '../src/Kernel.php';
//$_SERVER['REQUEST_URI'] = 'admin';
//(new \Snidget\Kernel())->run();

include './vendor/autoload.php';

use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\HttpServer;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Socket\Server;
use Psr\Log\NullLogger;
use Amp\Loop;

// https://www.youtube.com/watch?v=w75P9RrVgKg

Loop::run(function () {
    $sockets = [
        Server::listen("127.0.0.1:8000"),
    ];

    $server = new HttpServer($sockets, new CallableRequestHandler(
        function (Request $request) {
//            echo sprintf("\n%s %s %s\n\n", $request->getMethod(), $request->getUri(), $request->getProtocolVersion());
//            foreach ($request->getHeaders() as $name => $header) {
//                echo sprintf("%s: %s\n", $name, implode(',', $header));
//            }

            return new Response(Status::OK, [
                "content-type" => "text/plain; charset=utf-8"
            ], "Hello, World!");
        }),
        new NullLogger
    );

    yield $server->start();

    // gracefully
    Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
        Loop::cancel($watcherId);
        yield $server->stop();
    });
});