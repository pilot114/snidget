<?php

use PHPUnit\Framework\TestCase;
use Snidget\HTTP\Request;
use Snidget\HTTP\Response;
use Snidget\Kernel\MiddlewareManager;
use Snidget\Kernel\PSR\Container;

class MiddlewareTest extends TestCase
{
    public function testEmptyMiddlewareChain(): void
    {
        $container = new Container();
        $manager = new MiddlewareManager([], $container);

        $request = new Request();
        $request->uri = 'test';
        $request->method = 'GET';

        $result = $manager->handle($request, fn($req): string => 'core result');
        $this->assertEquals('core result', $result);
    }

    public function testCoreReceivesRequest(): void
    {
        $container = new Container();
        $manager = new MiddlewareManager([], $container);

        $request = new Request();
        $request->uri = 'test';
        $request->method = 'GET';

        $receivedRequest = null;
        $manager->handle($request, function ($req) use (&$receivedRequest): string {
            $receivedRequest = $req;
            return 'ok';
        });

        $this->assertInstanceOf(Request::class, $receivedRequest);
        $this->assertEquals('test', $receivedRequest->uri);
    }
}
