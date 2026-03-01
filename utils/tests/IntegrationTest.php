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

        $this->assertEquals('TestController', $controller);
        $this->assertEquals('index', $action);
        $this->assertEquals([], $params);
    }

    public function testNotFoundRouteReturns404(): void
    {
        $router = new Router();
        $router->register('hello', 'TestController::index');

        try {
            $router->match($this->makeRequest('nonexistent'));
            $this->fail('Ожидалось исключение');
        } catch (SnidgetException $e) {
            $this->assertEquals(404, $e->getCode());
        }
    }

    public function testMethodNotAllowed(): void
    {
        $router = new Router();
        $router->register('users', 'UserController::create', 'POST');

        try {
            $router->match($this->makeRequest('users', 'GET'));
            $this->fail('Ожидалось исключение');
        } catch (SnidgetException $e) {
            $this->assertEquals(405, $e->getCode());
        }
    }

    public function testResponseImmutability(): void
    {
        $response = new Response('Hello', 200);
        $modified = $response->withStatus(404)->withHeader('X-Test', 'value');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(404, $modified->getStatusCode());
        $this->assertEquals('value', $modified->getHeaders()['X-Test']);
        $this->assertArrayNotHasKey('X-Test', $response->getHeaders());
    }
}
