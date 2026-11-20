<?php

namespace Snidget\Kernel;

use Closure;
use Snidget\HTTP\Bind;
use Snidget\HTTP\Request;
use Snidget\Kernel\PSR\Container;

class MiddlewareManager
{
    /** @var array<string, Bind> */
    protected array $allMiddlewares = [];

    /** @var list<string> Класс::метод в порядке вызова */
    protected array $middlewares = [];

    /**
     * @param list<string> $middlewarePaths
     */
    public function __construct(
        protected array $middlewarePaths,
        protected Container $container
    ) {
        $this->allMiddlewares = iterator_to_array(AttributeLoader::getBinds($middlewarePaths));
    }

    /** @return list<string> */
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
            if ($c === null) {
                continue;
            }

            $binds[$m === null ? $c : "$c::$m"] = $attribute->getPriority();
        }

        arsort($binds);
        $this->middlewares = array_keys($binds);
        return $this;
    }

    /**
     * @param Closure(Request): mixed $core
     */
    public function handle(Request $request, Closure $core): mixed
    {
        return array_reduce(
            array_reverse($this->middlewares),
            $this->createLayer(...),
            $core
        )($request);
    }

    /** @return \Generator<string, int> */
    protected function getMiddlewareBinds(string $controller, string $action): \Generator
    {
        foreach ($this->allMiddlewares as $mwFqn => $attribute) {
            $c = $attribute->getClass();
            $m = $attribute->getMethod();
            $priority = $attribute->getPriority();
            if ($c === null || ($m === null && $c === $controller) || ($m === $action && $c === $controller)) {
                if (str_contains($mwFqn, '::')) {
                    yield $mwFqn => $priority;
                } else {
                    foreach (new Reflection($mwFqn)->getMethods() as $method) {
                        yield $mwFqn . '::' . $method->getName() => $priority;
                    }
                }
            }
        }
    }

    /**
     * @param Closure(Request): mixed $nextLayer
     * @return Closure(Request): mixed
     */
    protected function createLayer(Closure $nextLayer, string $layer): Closure
    {
        [$class, $method] = explode('::', $layer);
        return fn(Request $x): mixed => $this->container
            ->call($class, $method, ['request' => $x, 'next' => $nextLayer]);
    }
}
