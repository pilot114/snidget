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
        $appPath = dirname(__DIR__, 2) . '/App';
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
        $this->handle($router, $middlewareManager, $request)->send();
        exit;
    }

    public function prepare(): array
    {
        $router = $this->container->get(Router::class);
        foreach (AttributeLoader::getRoutes($this->config->getControllerPaths()) as $regex => $routeData) {
            $router->register($regex, $routeData['fqn'], $routeData['method']);
        }

        $middlewareManager = $this->container
            ->get(MiddlewareManager::class, ['middlewarePaths' => $this->config->getMiddlewarePaths()]);
        return [$router, $middlewareManager];
    }

    protected function handle(Router $router, MiddlewareManager $middlewareManager, Request $request): Response
    {
        try {
            $this->eventManager->emit(KernelEvent::REQUEST, $request);
            [$controller, $action, $params] = $router->match($request);
            $data = $middlewareManager
                ->match($controller, $action)
                ->handle(
                    $request,
                    fn(): mixed => $this->container->call($this->container->get($controller), $action, $params)
                );
            $response = $data instanceof Response ? $data : new Response($data);
        } catch (Throwable $throwable) {
            // клиенту — только статус, подробности — в обработчик ошибок
            $response = match ($throwable instanceof SnidgetException ? $throwable->getCode() : 0) {
                404 => new Response('Not Found', 404),
                405 => new Response('Method Not Allowed', 405),
                default => new Response('Internal Server Error', 500),
            };
            if ($response->getStatusCode() === 500) {
                $this->eventManager->emit(KernelEvent::ERROR, $throwable);
            }
        }

        $this->eventManager->emit(KernelEvent::RESPONSE, $response);
        return $response;
    }

    protected function setErrorReportingSettings(): void
    {
        ini_set('display_errors', 0);
        ini_set('display_startup_errors', 0);
        error_reporting(E_ALL);

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            // ошибка подавлена через @ или исключена из error_reporting
            if ((error_reporting() & $severity) === 0) {
                return false;
            }

            $this->eventManager->emit(KernelEvent::ERROR, new \ErrorException($message, 0, $severity, $file, $line));
            return true;
        });
        set_exception_handler(function (Throwable $exception): void {
            if (!headers_sent()) {
                http_response_code(500);
            }

            $this->eventManager->emit(KernelEvent::ERROR, $exception);
        });
    }
}
