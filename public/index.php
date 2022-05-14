<?php

use Snidget\{AttributeLoader, MiddlewareManager, Duck, Container, Request, Response, Router};

include_once './boot.php';

$container ??= new Container();
$router = $container->get(Router::class);

foreach (AttributeLoader::getRoutes('../app/HTTP/Controller') as $regex => $fqn) {
    $router->register($regex, $fqn);
}
$request = $container->get(Request::class);

if ($request->data) {
    $duck = new Duck('../app/DTO/API');
    $messages = [];
    foreach ($duck->layAnEgg($request->data) as $name => $errors) {
        $messages[] = sprintf("Поле %s не прошло валидацию: %s", $name, implode('|', $errors));
    }
    if ($messages) {
        dump($messages);
        die();
    }
}

list($controller, $action, $params) = $router->match($request);

$mwManager = new MiddlewareManager('../app/HTTP/Middleware', $container);
$data = $mwManager
    ->match($controller, $action)
    ->handle($request, fn() => $container->call($container->get($controller), $action, $params));
(new Response($data))->send();
