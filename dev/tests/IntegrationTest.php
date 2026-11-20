<?php

use PHPUnit\Framework\TestCase;
use Snidget\HTTP\Request;
use Snidget\HTTP\Response;
use Snidget\HTTP\Router;
use Snidget\Kernel\SnidgetException;

class IntegrationTest extends TestCase
{
    protected function makeRequest(string $uri, string $method = 'GET'): Request
    {
        $request = new Request();
        $request->uri = $uri;
        $request->method = $method;
        return $request;
    }

    public function testRouterToControllerFlow(): void
    {
        $router = new Router();
        $router->register('hello', 'TestController::index', 'GET');

        $request = $this->makeRequest('hello', 'GET');
        [$controller, $action, $params] = $router->match($request);

        self::assertEquals('TestController', $controller);
        self::assertEquals('index', $action);
        self::assertEquals([], $params);
    }

    public function testNotFoundRouteReturns404(): void
    {
        $router = new Router();
        $router->register('hello', 'TestController::index');

        try {
            $router->match($this->makeRequest('nonexistent'));
            self::fail('Ожидалось исключение');
        } catch (SnidgetException $snidgetException) {
            self::assertEquals(404, $snidgetException->getCode());
        }
    }

    public function testMethodNotAllowed(): void
    {
        $router = new Router();
        $router->register('users', 'UserController::create', 'POST');

        try {
            $router->match($this->makeRequest('users', 'GET'));
            self::fail('Ожидалось исключение');
        } catch (SnidgetException $snidgetException) {
            self::assertEquals(405, $snidgetException->getCode());
        }
    }

    public function testResponseImmutability(): void
    {
        $response = new Response('Hello', 200);
        $modified = $response->withStatus(404)->withHeader('X-Test', 'value');

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals(404, $modified->getStatusCode());
        self::assertEquals('value', $modified->getHeaders()['X-Test']);
        self::assertArrayNotHasKey('X-Test', $response->getHeaders());
    }
}
