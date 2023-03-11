<?php

namespace Snidget;

use Snidget\Async\Debug;
use Snidget\Async\Scheduler;
use Snidget\Async\Server;
use Snidget\Enum\SystemEvent;
use Snidget\Schema\Config\AppPaths;
use Snidget\Psr\Container;
use Snidget\Psr\EventManager;
use Throwable;

class Kernel
{
    protected Container $container;
    protected AppPaths $config;
    protected EventManager $eventManager;
    public static string $appPath;
    public bool $isAsync = false;
    public bool $displayAllErrors = true;

    public function __construct(bool $isAsync = false, ?string $appPath = null)
    {
        self::$appPath = $appPath ?? dirname(__DIR__) . '/App';
        $this->isAsync = $isAsync;

        $this->container = new Container();
        $eventManager = $this->container->get(EventManager::class);
        $eventManager->register(self::$appPath);
        $eventManager->emit(SystemEvent::START);
        if (!$isAsync) {
            $eventManager->emit(
                SystemEvent::REQUEST,
                $this->container->get(Request::class)->fromGlobal()
            );
        }

        $this->eventManager = $eventManager;
        $this->config = $this->container->get(AppPaths::class, ['appPath' => self::$appPath]);

        $this->unexpectedErrorHandler();
    }

    public function run(): never
    {
        $router = $this->container->get(Router::class);
        foreach (AttributeLoader::getRoutes($this->config->getControllerPaths()) as $regex => $fqn) {
            $router->register($regex, $fqn);
        }
        $middlewareManager = $this->container
            ->get(MiddlewareManager::class, ['middlewarePaths' => $this->config->getMiddlewarePaths()]);
        $request = $this->container->get(Request::class);

        if (!$this->isAsync) {
            $data = $this->handle($router, $middlewareManager, $request->fromGlobal());
            (new Response($data))->send();
            exit;
        }

        $this->eventManager->emit(SystemEvent::REQUEST, $request);
        $this->async(fn($request) => $this->handle($router, $middlewareManager, $request), $request);
    }

    public function overrideRequest(string $uri, string $method, array $payload): self
    {
        $request = $this->container->make(Request::class);
        $request->uri = $uri;
        $request->method = $method;
        $request->payload = $payload;
        $request->isOverride = true;
        return $this;
    }

    protected function handle(Router $router, MiddlewareManager $middlewareManager, Request $request): string
    {
        [$controller, $action, $params] = $router->match($request);
        $data = $middlewareManager
            ->match($controller, $action)
            ->handle($request, fn() => $this->container->call($this->container->get($controller), $action, $params));
        $this->eventManager->emit(SystemEvent::RESPONSE, $data);
        return $data;
    }

    protected function async(\Closure $kernelHandler, Request $request): never
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
            if ($this->displayAllErrors && $error = error_get_last()) {
                dump(sprintf('Fatal %s: %s', $error['type'], $error['message']));
                dump($error['file'] . ':' . $error['line']);
            }
            $this->eventManager->emit(SystemEvent::FINISH);
        });
        set_exception_handler(function (Throwable $exception) {
            $this->eventManager->emit(SystemEvent::EXCEPTION, $exception);

            if ($this->displayAllErrors) {
                dump(get_class($exception) . ': ' . $exception->getMessage());
                dump($exception->getFile() . ':' . $exception->getLine());
                dump($exception->getTraceAsString());
            }
        });

        if (!$this->displayAllErrors) {
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
