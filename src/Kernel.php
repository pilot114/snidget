<?php

namespace
{
    function dump(...$vars): void
    {
        foreach ($vars as $var) {
            echo '<pre>' . print_r($var, true) . '</pre>';
        }
    }
}

namespace Snidget
{
    use Throwable;

    class Kernel
    {
        protected Container $container;

        public function __construct()
        {
            $this->autoload('Snidget\\', __DIR__ . '/../src/');
            $this->autoload('App\\', __DIR__ . '/../app/');
            $this->errorHandler();
            $this->container = new Container();
        }

        public function run(): never
        {
            $router = $this->container->get(Router::class);

            foreach (AttributeLoader::getRoutes('../app/HTTP/Controller') as $regex => $fqn) {
                $router->register($regex, $fqn);
            }
            $request = $this->container->get(Request::class);
            list($controller, $action, $params) = $router->match($request);

            $mwManager = $this->container->get(MiddlewareManager::class, ['middlewarePath' => '../app/HTTP/Middleware']);
            $data = $mwManager
                ->match($controller, $action)
                ->handle($request, fn() => $this->container->call($this->container->get($controller), $action, $params));
            (new Response($data))->send();
        }

        public function overrideRequest(string $uri, array $data): self
        {
            $request = $this->container->get(Request::class);
            $request->uri = $uri;
            $request->data = $data;
            return $this;
        }

        static public function psrIterator(string $controllerPath): iterable
        {
            $parts = array_filter(explode('/', trim($controllerPath, '.')));
            $controllerNamespace = '\\' . implode('\\', array_map(ucfirst(...), $parts)) . '\\';
            foreach (glob($controllerPath . '/*') as $controller) {
                preg_match("#/(?<className>\w+)\.php#i", $controller, $matches);
                yield $controllerNamespace . $matches['className'];
            }
        }

        protected function autoload($prefix, $baseDir): void
        {
            spl_autoload_register(function($class) use ($prefix, $baseDir) {
                $len = strlen($prefix);
                if (strncmp($prefix, $class, $len) !== 0) {
                    return;
                }
                $relativeClass = substr($class, $len);
                $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
                if (file_exists($file)) {
                    require $file;
                }
            });
        }

        protected function errorHandler(): void
        {
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);

            set_error_handler(function (int $code, string $message, string $file, int $line): bool {
                dump(sprintf('error (%s): %s', $code, $message));
                dump($file . ':' . $line);
                return true;
            });
            set_exception_handler(function (Throwable $exception) {
                dump(get_class($exception) . ': ' . $exception->getMessage());
                dump($exception->getFile() . ':' . $exception->getLine());
                dump($exception->getTraceAsString());
            });
        }
    }
}