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
    $router = $container->get(Router::class);

    $attributeLoader = new AttributeLoader('../app/Controller', '\\App\\Controller\\');
    foreach ($attributeLoader->getRoutes() as $regex => $fqdn) {
        $router->register($regex, $fqdn);
    }

    list($controller, $action, $params) = $router->match($container->get(Request::class));
    $controller = $container->get($controller);
    $data = $container->call($controller, $action, $params);
    $response = new Response($data);
    $response->send();
}
