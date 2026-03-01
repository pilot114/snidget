<?php

use PHPUnit\Framework\TestCase;
use Snidget\HTTP\Request;
use Snidget\HTTP\Router;
use Snidget\Kernel\SnidgetException;

class RouterTest extends TestCase
{
    protected function makeRequest(string $uri, string $method = 'GET'): Request
    {
        $request = new Request();
        $request->uri = $uri;
        $request->method = $method;
        return $request;
    }

    public function testRegisterAndMatch(): void
    {
        $router = new Router();
        $router->register('test', 'Controller::action');

        $result = $router->match($this->makeRequest('test'));
        $this->assertEquals('Controller', $result[0]);
        $this->assertEquals('action', $result[1]);
    }

    public function testNamedGroups(): void
    {
        $router = new Router();
        $router->register('users/(?P<id>\d+)', 'UserController::show');

        $result = $router->match($this->makeRequest('users/42'));
        $this->assertEquals('42', $result[2]['id']);
    }

    public function testMethodMismatch(): void
    {
        $router = new Router();
        $router->register('users', 'UserController::list', 'GET');

        $this->expectException(SnidgetException::class);
        $this->expectExceptionCode(405);
        $router->match($this->makeRequest('users', 'POST'));
    }

    public function testNoMatch(): void
    {
        $router = new Router();
        $router->register('users', 'UserController::list');

        $this->expectException(SnidgetException::class);
        $this->expectExceptionCode(404);
        $router->match($this->makeRequest('nonexistent'));
    }

    public function testSameUriDifferentMethods(): void
    {
        $router = new Router();
        $router->register('users', 'UserController::list', 'GET');
        $router->register('users', 'UserController::create', 'POST');

        $getResult = $router->match($this->makeRequest('users', 'GET'));
        $this->assertEquals('list', $getResult[1]);

        $postResult = $router->match($this->makeRequest('users', 'POST'));
        $this->assertEquals('create', $postResult[1]);
    }
}
