<?php

use Snidget\{AttributeLoader, MiddlewareManager, Container, Request, Response, Router};

include './helpers.php';

autoload('Snidget\\', __DIR__ . '/../src/');
autoload('App\\', __DIR__ . '/../app/');
errorHandler();

$container = new Container();
$router = $container->get(Router::class);

foreach (AttributeLoader::getRoutes('../app/HTTP/Controller', '\\App\\HTTP\\Controller\\') as $regex => $fqn) {
    $router->register($regex, $fqn);
}
$request = $container->get(Request::class);
list($controller, $action, $params) = $router->match($request);

$mwManager = new MiddlewareManager('../app/HTTP/Middleware', '\\App\\HTTP\\Middleware\\', $container);
$data = $mwManager
    ->match($controller, $action)
    ->handle($request, function() use ($container, $controller, $action, $params) {
        $controller = $container->get($controller);
        return $container->call($controller, $action, $params);
    });
(new Response($data))->send();
