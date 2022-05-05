<?php

use Wshell\Snidget\{AttributeLoader, Container, Request, Response, Router};

include './utils.php';

autoload('Wshell\\Snidget\\', __DIR__ . '/../src/');
autoload('App\\', __DIR__ . '/../app/');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$isCli = php_sapi_name() === 'cli';

if ($isCli) {

} else {
    $request = new Request();
    $router = new Router();
    $container = new Container();

    $attributeLoader = new AttributeLoader('../app/Controller', '\\App\\Controller\\');
    $attributeLoader->handleRoute(fn($regex, $fqdn, $action) => $router->register($regex, $fqdn, $action));

    list($controller, $action, $params) = $router->match($request);
    $data = $container->controllerCall($controller, $action, $params);
    $response = new Response($data);
    $response->send();
}
