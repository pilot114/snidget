<?php

use PHPUnit\Framework\TestCase;
use Snidget\HTTP\Request;
use Snidget\Kernel\MiddlewareManager;
use Snidget\Kernel\PSR\Container;
use Snidget\Tests\Fixtures\FixtureController;
use Snidget\Tests\Fixtures\FixtureMiddleware;
use Dev\Tests\Fixtures\Middleware\TwiceMiddleware;

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
        self::assertEquals('core result', $result);
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

        self::assertInstanceOf(Request::class, $receivedRequest);
        self::assertEquals('test', $receivedRequest->uri);
    }

    public function testControllerBindsMiddlewareMethodOrWholeClass(): void
    {
        include_once __DIR__ . '/fixtures/Handlers.php';
        $manager = new MiddlewareManager([], new Container());

        foreach (['one', 'two'] as $action) {
            $layers = $manager->match(FixtureController::class, $action)->getMiddlewares();
            self::assertSame([FixtureMiddleware::class . '::wrap'], $layers);
            self::assertSame('wrapped core', $manager->handle(new Request(), fn(): string => 'core'));
        }
    }

    public function testRepeatedBindAndOnlyPublicMethodsBecomeLayers(): void
    {
        include_once __DIR__ . '/fixtures/Handlers.php';
        include_once __DIR__ . '/fixtures/Middleware/TwiceMiddleware.php';
        $manager = new MiddlewareManager([__DIR__ . '/fixtures/Middleware'], new Container());

        foreach (['one', 'two'] as $action) {
            self::assertSame(
                [TwiceMiddleware::class . '::mark', FixtureMiddleware::class . '::wrap'],
                $manager->match(FixtureController::class, $action)->getMiddlewares()
            );
        }
    }
}
