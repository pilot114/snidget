<?php

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Monolog\Logger;
use Monolog\Level;
use Monolog\Handler\StreamHandler;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ServerRequestInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\HttpClient\Psr18Client;
use SnidgetAI\Routing\RouterInterface;
use SnidgetAI\Routing\Router;
use SnidgetAI\Http\ResponseEmitterInterface;
use SnidgetAI\Http\ResponseEmitter;

return [
    LoggerInterface::class => DI\factory(function() {
        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler('php://stdout', Level::Debug));
        return $logger;
    }),

    Psr\Http\Message\RequestFactoryInterface::class => DI\create(Psr17Factory::class),
    Psr\Http\Message\ResponseFactoryInterface::class => DI\create(Psr17Factory::class),
    Psr\Http\Message\StreamFactoryInterface::class => DI\create(Psr17Factory::class),
    Psr\Http\Message\UploadedFileFactoryInterface::class => DI\create(Psr17Factory::class),
    Psr\Http\Message\ServerRequestFactoryInterface::class => DI\create(Psr17Factory::class),

    ServerRequestInterface::class => DI\factory(function(ContainerInterface $c) {
        $psr17Factory = $c->get(Psr17Factory::class);
        $creator = new ServerRequestCreator(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );
        return $creator->fromGlobals();
    }),

    EventDispatcherInterface::class => DI\create(EventDispatcher::class),
    ClientInterface::class => DI\create(Psr18Client::class),
    RouterInterface::class => DI\create(Router::class),
    ResponseEmitterInterface::class => DI\create(ResponseEmitter::class),
];
