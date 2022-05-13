<?php

namespace Snidget;

use Snidget\Module\Reflection;
use Closure;

class MiddlewareManager
{
    protected array $middlewares = [];

    public function __construct(
        protected string $controllerPath,
        protected string $controllerNamespace,
        protected Container $container
    ){}

    public function match(string $controller, string $action): self
    {
        $middlewares = [];
        $attributes = AttributeLoader::getBinds($this->controllerPath, $this->controllerNamespace);
        foreach ($attributes as $mwFqn => $attribute) {
            list($c, $m, $priority) = [$attribute->getClass(), $attribute->getMethod(), $attribute->getPriority()];
            if (!$c || (!$m && $c === $controller) || ($m === $action && $c === $controller)) {
                if (str_contains($mwFqn, '::')) {
                    $middlewares[$mwFqn] = $priority;
                } else {
                    foreach ((new Reflection($mwFqn))->getMethods() as $method) {
                        $middlewares[$mwFqn . '::' . $method->getName()] = $priority;
                    }
                }
            }
        }
        foreach (AttributeLoader::getBindsByAction($controller, $action) as $attribute) {
            list($c, $m, $priority) = [$attribute->getClass(), $attribute->getMethod(), $attribute->getPriority()];
            $mwFqn = $m ? ($c . '::' . $m) : $c;
            $middlewares[$mwFqn] = $priority;
        }
        arsort($middlewares);
        $middlewares = array_keys($middlewares);
        $middlewares = array_map(fn($x) => explode('::', $x), $middlewares);
        $this->middlewares = $middlewares;
        return $this;
    }

    public function handle(Request $request, Closure $core): string
    {
        $coreFunction = fn($object) => $core($object);
        $layers = array_reverse($this->middlewares);
        $onion = array_reduce(
            $layers,
            fn($nextLayer, $layer) => $this->createLayer($nextLayer, $layer),
            $coreFunction
        );
        return $onion($request);
    }

    private function createLayer($nextLayer, $layer): Closure
    {
        list($class, $method) = $layer;
        $class = $this->container->get($class);
        return fn($x) => $this->container->call($class, $method, ['request' => $x, 'next' => $nextLayer]);
    }
}