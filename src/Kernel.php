<?php

namespace Snidget;

use Snidget\Async\Debug;
use Snidget\Async\Scheduler;
use Snidget\Async\Server;
use Snidget\DTO\Config\App;
use Snidget\Enum\SystemEvent;
use Throwable;

class Kernel
{
    protected Container $container;
    protected App $config;
    protected EventManager $eventManager;
    public static string $appPath;

    public function __construct(?string $appPath = null)
    {
        self::$appPath = $appPath ?? dirname(__DIR__) . '/app';

        $this->container = new Container();
        $eventManager = $this->container->get(EventManager::class);
        $eventManager->register(self::$appPath);
        $eventManager->emit(SystemEvent::START);
        $this->eventManager = $eventManager;

        $this->config = $this->container->get(App::class, ['appPath' => self::$appPath]);

        $this->unexpectedErrorHandler();
    }

    public function run(bool $isAsync = false): never
    {
        $router = $this->container->get(Router::class);
        foreach (AttributeLoader::getRoutes($this->config->getControllerPaths()) as $regex => $fqn) {
            $router->register($regex, $fqn);
        }
        $middlewareManager = $this->container
            ->get(MiddlewareManager::class, ['middlewarePaths' => $this->config->getMiddlewarePaths()]);
        $request = $this->container->get(Request::class);

        // async mode
        if ($isAsync) {
            $this->async(fn($request) => $this->handle($router, $middlewareManager, $request), $request);
            exit;
        }

        $data = $this->handle($router, $middlewareManager, $request->fromGlobal());
        (new Response($data))->send();
        exit;
    }

    public function overrideRequest(string $uri, string $method, array $payload): self
    {
        $request = $this->container->make(Request::class);
        $request->uri = $uri;
        $request->method = $method;
        $request->payload = $payload;
        $request->isOverrided = true;
        return $this;
    }

    protected function handle(Router $router, MiddlewareManager $middlewareManager, Request $request): string
    {
        [$controller, $action, $params] = $router->match($request);
        $data = $middlewareManager
            ->match($controller, $action)
            ->handle($request, fn() => $this->container->call($this->container->get($controller), $action, $params));
        $this->eventManager->emit(SystemEvent::SEND, $data);
        return $data;
    }

    protected function async(\Closure $kernelHandler, Request $request): void
    {
        Server::$kernelHandler = $kernelHandler;
        Server::$request = $request;
        $scheduler = new Scheduler([
            Server::http(...),
        ], $this->container->get(Debug::class));
        $scheduler->run();
    }

    protected function unexpectedErrorHandler(): void
    {
        register_shutdown_function(function () {
            if ($this->config->displayAllErrors && $error = error_get_last()) {
                dump(sprintf('Fatal %s: %s', $error['type'], $error['message']));
                dump($error['file'] . ':' . $error['line']);
            }
            $this->eventManager->emit(SystemEvent::FINISH);
        });
        set_exception_handler(function (Throwable $exception) {
            $this->eventManager->emit(SystemEvent::EXCEPTION, $exception);

            if ($this->config->displayAllErrors) {
                dump(get_class($exception) . ': ' . $exception->getMessage());
                dump($exception->getFile() . ':' . $exception->getLine());
                dump($exception->getTraceAsString());
            }
        });

        if (!$this->config->displayAllErrors) {
            return;
        }

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        set_error_handler(function (int $code, string $message, string $file, int $line): bool {
            dump(sprintf('error %s: %s', $code, $message));
            dump($file . ':' . $line);
            return true;
        });
    }
}
