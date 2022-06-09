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
    use Snidget\DTO\Config\App;
    use Throwable;

    class Kernel
    {
        protected Container $container;
        protected App $config;
        protected static string $appPath;

        public function __construct($appPath = null)
        {
            self::$appPath = $appPath ?? dirname(__DIR__) . '/app';

            if (!$appPath) {
                $this->autoload('Snidget\\', __DIR__ . '/');
                $this->autoload('App\\', self::$appPath . '/');
            }

            $this->container = new Container();
            $this->config = $this->container->get(App::class, ['appPath' => self::$appPath]);

            if ($this->config->displayAllErrors) {
                $this->errorHandler();
            }
        }

        public function run(): never
        {
            $router = $this->container->get(Router::class);

            foreach (AttributeLoader::getRoutes($this->config->getControllerPath()) as $regex => $fqn) {
                $router->register($regex, $fqn);
            }
            $request = $this->container->get(Request::class);
            list($controller, $action, $params) = $router->match($request);

            $mwManager = $this->container
                ->get(MiddlewareManager::class, ['middlewarePath' => $this->config->getMiddlewarePath()]);
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

        /**
         * @return iterable<string>
         */
        static public function psrIterator(string $classPath): iterable
        {
            $relPath = str_replace(self::$appPath, 'app', $classPath);
            $parts = array_filter(explode('/', trim($relPath, '.')));
            $classNamespace = '\\' . implode('\\', array_map(ucfirst(...), $parts)) . '\\';
            foreach (glob($classPath . '/*') as $class) {
                preg_match("#/(?<className>\w+)\.php#i", $class, $matches);
                yield $classNamespace . $matches['className'];
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