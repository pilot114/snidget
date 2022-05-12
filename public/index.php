<?php

use Snidget\{AttributeLoader, Container, Request, Response, Router};

include './helpers.php';

autoload('Snidget\\', __DIR__ . '/../src/');
autoload('App\\', __DIR__ . '/../app/');
errorHandler();

$container = new Container();
$router = $container->get(Router::class);

foreach (AttributeLoader::getRoutes('../app/HTTP/Controller', '\\App\\HTTP\\Controller\\') as $regex => $fqn) {
    $router->register($regex, $fqn);
}
foreach (AttributeLoader::getBinds('../app/HTTP/Middleware', '\\App\\HTTP\\Middleware\\') as $from => $to) {
    dump($from);
    dump([$to->getPriority(), $to->getClass(), $to->getMethod()]);
}

list($controller, $action, $params) = $router->match($container->get(Request::class));
$controller = $container->get($controller);
$data = $container->call($controller, $action, $params);
$response = new Response($data);
$response->send();
