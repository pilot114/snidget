<?php

namespace SnidgetAI;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use SnidgetAI\Http\ResponseEmitterInterface;
use SnidgetAI\Routing\RouterInterface;

class Kernel implements KernelInterface
{
    protected ContainerInterface $container;
    protected RouterInterface $router;
    protected array $middlewareQueue = [];
    protected EventDispatcherInterface $eventDispatcher;
    protected LoggerInterface $logger;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->router = $this->container->get(RouterInterface::class);
        $this->eventDispatcher = $this->container->get(EventDispatcherInterface::class);
        $this->logger = $this->container->get(LoggerInterface::class);
    }

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewareQueue[] = $middleware;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $handler = new class($this->middlewareQueue, $this->router) implements RequestHandlerInterface {
            private array $middlewareQueue;
            private RouterInterface $router;
            private int $index = 0;

            public function __construct(array $middlewareQueue, RouterInterface $router)
            {
                $this->middlewareQueue = $middlewareQueue;
                $this->router = $router;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                if (isset($this->middlewareQueue[$this->index])) {
                    $middleware = $this->middlewareQueue[$this->index];
                    $this->index++;
                    return $middleware->process($request, $this);
                } else {
                    return $this->router->dispatch($request);
                }
            }
        };

        return $handler->handle($request);
    }

    public function run(): void
    {
        $request = $this->container->get(ServerRequestInterface::class);
        $response = $this->handle($request);

        /** @var ResponseEmitterInterface $emitter */
        $emitter = $this->container->get(ResponseEmitterInterface::class);
        $emitter->emit($response);
    }
}
