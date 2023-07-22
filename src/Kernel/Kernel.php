<?php

namespace Snidget\Kernel;

use Snidget\HTTP\Request;
use Snidget\HTTP\Response;
use Snidget\HTTP\Router;
use Snidget\Kernel\PSR\Container;
use Snidget\Kernel\PSR\Event\EventManager;
use Snidget\Kernel\PSR\Event\KernelEvent;
use Snidget\Kernel\Schema\AppPaths;
use Throwable;

class Kernel
{
    protected Container $container;
    protected AppPaths $config;
    protected EventManager $eventManager;
    public static string $appPath;
    public bool $displayAllErrors = true;

    public function __construct(?string $appPath = null, bool $emitRequest = true)
    {
        self::$appPath = $appPath ?? dirname(__DIR__, 2) . '/App';

        $this->container = new Container();
        $eventManager = $this->container->get(EventManager::class);
        $eventManager->register(self::$appPath);
        $eventManager->emit(KernelEvent::START);
        if ($emitRequest) {
            $eventManager->emit(
                KernelEvent::REQUEST,
                $this->container->get(Request::class)->fromGlobal()
            );
        }

        $this->eventManager = $eventManager;
        $this->config = $this->container->get(AppPaths::class, ['appPath' => self::$appPath]);

        $this->unexpectedErrorHandler();
    }

    public function run(): never
    {
        [$router, $middlewareManager, $request] = $this->prepare();
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
        $request->isOverride = true;
        return $this;
    }

    public function prepare(): array
    {
        $router = $this->container->get(Router::class);
        foreach (AttributeLoader::getRoutes($this->config->getControllerPaths()) as $regex => $fqn) {
            $router->register($regex, $fqn);
        }
        $middlewareManager = $this->container
            ->get(MiddlewareManager::class, ['middlewarePaths' => $this->config->getMiddlewarePaths()]);
        $request = $this->container->get(Request::class);

        return [$router, $middlewareManager, $request];
    }

    protected function handle(Router $router, MiddlewareManager $middlewareManager, Request $request): string
    {
        [$controller, $action, $params] = $router->match($request);
        $data = $middlewareManager
            ->match($controller, $action)
            ->handle($request, fn() => $this->container->call($this->container->get($controller), $action, $params));
        $this->eventManager->emit(KernelEvent::RESPONSE, $data);
        return $data;
    }

    protected function unexpectedErrorHandler(): void
    {
        register_shutdown_function(function () {
            if ($this->displayAllErrors && $error = error_get_last()) {
                dump(sprintf('Fatal %s: %s', $error['type'], $error['message']));
                dump($error['file'] . ':' . $error['line']);
            }
            $this->eventManager->emit(KernelEvent::FINISH);
        });
        set_exception_handler(function (Throwable $exception) {
            $this->eventManager->emit(KernelEvent::EXCEPTION, $exception);

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
