<?php

namespace Snidget\Kernel;

use Closure;
use Snidget\HTTP\Request;
use Snidget\Kernel\PSR\Container;

class MiddlewareManager
{
    protected array $allMiddlewares = [];
    protected array $middlewares = [];

    public function __construct(
        protected array $middlewarePaths,
        protected Container $container
    ) {
        $this->allMiddlewares = iterator_to_array(AttributeLoader::getBinds($middlewarePaths));
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function match(string $controller, string $action): self
    {
        $binds = iterator_to_array($this->getMiddlewareBinds($controller, $action));
        foreach (AttributeLoader::getBindsByAction($controller, $action) as $attribute) {
            $c = $attribute->getClass();
            $m = $attribute->getMethod();
            $priority = $attribute->getPriority();
            $mwFqn = $m ? ($c . '::' . $m) : $c;
            $binds[$mwFqn] = $priority;
        }
        arsort($binds);
        $this->middlewares = array_map(fn($x) => explode('::', $x), array_keys($binds));
        return $this;
    }

    public function handle(Request $request, Closure $core): string
    {
        return array_reduce(
            array_reverse($this->middlewares),
            fn($nextLayer, $layer): \Closure => $this->createLayer($nextLayer, $layer),
            fn($object) => $core($object)
        )($request);
    }

    protected function getMiddlewareBinds(string $controller, string $action): \Generator
    {
        foreach ($this->allMiddlewares as $mwFqn => $attribute) {
            $c = $attribute->getClass();
            $m = $attribute->getMethod();
            $priority = $attribute->getPriority();
            if (!$c || (!$m && $c === $controller) || ($m === $action && $c === $controller)) {
                if (str_contains($mwFqn, '::')) {
                    yield $mwFqn => $priority;
                } else {
                    foreach ((new Reflection($mwFqn))->getMethods() as $method) {
                        yield $mwFqn . '::' . $method->getName() => $priority;
                    }
                }
            }
        }
    }

    protected function createLayer(Closure $nextLayer, array $layer): Closure
    {
        [$class, $method] = $layer;
        $class = $this->container->get($class);
        return fn($x) => $this->container->call($class, $method, ['request' => $x, 'next' => $nextLayer]);
    }
}
