<?php

require_once __DIR__ . '/../vendor/autoload.php';

use DI\ContainerBuilder;
use Nyholm\Psr7\Factory\Psr17Factory;
use SnidgetAI\Kernel;
use SnidgetAI\Routing\RouterInterface;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../config/main.php');
$container = $containerBuilder->build();

$kernel = new Kernel($container);

// Добавляем middleware (если есть)
// $kernel->addMiddleware(new SomeMiddleware());

$router = $container->get(RouterInterface::class);
$router->addRoute('GET', '/', function($request) {
    $responseFactory = new Psr17Factory();
    $response = $responseFactory->createResponse(200);
    $response->getBody()->write("Hello, World!");
    return $response;
});

$kernel->run();
