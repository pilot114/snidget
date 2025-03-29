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
    protected EventManager $eventManager;
    protected AppPaths $config;

    public function __construct()
    {
        $appPath = '/app/App';
        $this->container = new Container();

        $this->eventManager = $this->container->get(EventManager::class);
        $this->eventManager->register(__DIR__, 'Snidget');
        $this->eventManager->register($appPath);
        $this->eventManager->emit(KernelEvent::START);

        $this->config = $this->container->get(AppPaths::class, ['appPath' => $appPath]);
        $this->setErrorReportingSettings();
        register_shutdown_function(fn() => $this->eventManager->emit(KernelEvent::FINISH));
    }

    public function run(?Request $request = null): never
    {
        [$router, $middlewareManager] = $this->prepare();
        $request ??= $this->container->get(Request::class)->fromGlobal();
        $data = $this->handle($router, $middlewareManager, $request);
        (new Response($data))->send();
        exit;
    }

    public function prepare(): array
    {
        $router = $this->container->get(Router::class);
        foreach (AttributeLoader::getRoutes($this->config->getControllerPaths()) as $regex => $fqn) {
            $router->register($regex, $fqn);
        }
        $middlewareManager = $this->container
            ->get(MiddlewareManager::class, ['middlewarePaths' => $this->config->getMiddlewarePaths()]);
        return [$router, $middlewareManager];
    }

    protected function handle(Router $router, MiddlewareManager $middlewareManager, Request $request): string
    {
        $this->eventManager->emit(KernelEvent::REQUEST, $request);
        [$controller, $action, $params] = $router->match($request);
        $data = $middlewareManager
            ->match($controller, $action)
            ->handle($request, fn(): mixed => $this->container->call($this->container->get($controller), $action, $params));
        $this->eventManager->emit(KernelEvent::RESPONSE, $data);
        return $data;
    }

    protected function setErrorReportingSettings(): void
    {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(E_ALL);

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            $this->eventManager->emit(KernelEvent::ERROR, new \ErrorException($message, 0, $severity, $file, $line));
            return true;
        });
        set_exception_handler(fn (Throwable $exception) => $this->eventManager->emit(KernelEvent::ERROR, $exception));
    }
}
