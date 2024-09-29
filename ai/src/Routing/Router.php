<?php

namespace SnidgetAI\Routing;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class Router implements RouterInterface
{
    protected array $routes = [];

    public function addRoute(string $method, string $path, callable $handler): void
    {
        $this->routes[$method][$path] = $handler;
    }

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        if (isset($this->routes[$method][$path])) {
            $handler = $this->routes[$method][$path];
            return call_user_func($handler, $request);
        }

        // Возвращаем 404 ответ, если маршрут не найден
        $responseFactory = new \Nyholm\Psr7\Factory\Psr17Factory();
        return $responseFactory->createResponse(404, 'Not Found');
    }
}