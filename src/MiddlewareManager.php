<?php

namespace Snidget;

use Snidget\Module\Reflection;
use Closure;

class MiddlewareManager
{
    protected array $middlewares = [];

    public function __construct(
        protected array $middlewarePaths,
        protected Container $container
    ){}

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function register(): self
    {
        // TODO: предварительная регистрация может облегчить match (актуально в асинхронном режиме)
    }

    public function match(string $controller, string $action): self
    {
        $binds = iterator_to_array($this->getMiddlewareBinds($controller, $action));
        foreach (AttributeLoader::getBindsByAction($controller, $action) as $attribute) {
            list($c, $m, $priority) = [$attribute->getClass(), $attribute->getMethod(), $attribute->getPriority()];
            $mwFqn = $m ? ($c . '::' . $m) : $c;
            $binds[$mwFqn] = $priority;
        }
        arsort($binds);
        $binds = array_map(fn($x) => explode('::', $x), array_keys($binds));
        $this->middlewares = $binds;
        return $this;
    }

    public function handle(Request $request, Closure $core): string
    {
        return array_reduce(
            array_reverse($this->middlewares),
            fn($nextLayer, $layer) => $this->createLayer($nextLayer, $layer),
            fn($object) => $core($object)
        )($request);
    }

    protected function getMiddlewareBinds(string $controller, string $action): iterable
    {
        foreach (AttributeLoader::getBinds($this->middlewarePaths) as $mwFqn => $attribute) {
            list($c, $m, $priority) = [$attribute->getClass(), $attribute->getMethod(), $attribute->getPriority()];
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

    protected function createLayer($nextLayer, $layer): Closure
    {
        list($class, $method) = $layer;
        $class = $this->container->get($class);
        return fn($x) => $this->container->call($class, $method, ['request' => $x, 'next' => $nextLayer]);
    }
}