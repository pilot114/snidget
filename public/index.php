<?php

use Snidget\{AttributeLoader, Container, Request, Response, Router};

include './utils.php';

autoload('Snidget\\', __DIR__ . '/../src/');
autoload('App\\', __DIR__ . '/../app/');
errorHandler();

$isCli = php_sapi_name() === 'cli';

if ($isCli) {

} else {
    $container = new Container();

    $request = $container->get(Request::class);
    $router = $container->get(Router::class);

    $attributeLoader = new AttributeLoader('../app/Controller', '\\App\\Controller\\');
    $attributeLoader->handleRoute(fn($regex, $fqdn, $action) => $router->register($regex, $fqdn, $action));

    list($controller, $action, $params) = $router->match($request);
    $controller = $container->get($controller);
    $data = $container->call($controller, $action, $params);
    $response = new Response($data);
    $response->send();
}
