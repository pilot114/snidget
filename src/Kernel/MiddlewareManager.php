<?php

namespace Snidget\Kernel;

use Closure;
use Snidget\HTTP\Bind;
use Snidget\HTTP\Request;
use Snidget\Kernel\PSR\Container;

class MiddlewareManager
{
    /** @var list<array{string, Bind}> Class or Class::method with its Bind, a class can have several */
    protected array $allMiddlewares = [];

    /** @var list<string> Class::method in call order */
    protected array $middlewares = [];

    /**
     * @param list<string> $middlewarePaths
     */
    public function __construct(
        protected array $middlewarePaths,
        protected Container $container
    ) {
        foreach (AttributeLoader::getBinds($middlewarePaths) as $fqn => $bind) {
            $this->allMiddlewares[] = [$fqn, $bind];
        }
    }

    /** @return list<string> */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function match(string $controller, string $action): self
    {
        $binds = [];
        foreach ($this->allMiddlewares as [$fqn, $attribute]) {
            $c = $attribute->getClass();
            $m = $attribute->getMethod();
            if ($c === null || ($c === $controller && ($m === null || $m === $action))) {
                $binds = [...$binds, ...$this->layers($fqn, $attribute->getPriority())];
            }
        }

        // on a controller, Bind points to the middleware
        foreach (AttributeLoader::getBindsByAction($controller, $action) as $attribute) {
            $c = $attribute->getClass();
            $m = $attribute->getMethod();
            if ($c !== null) {
                $binds = [...$binds, ...$this->layers($m === null ? $c : "$c::$m", $attribute->getPriority())];
            }
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

    /** @return array<string, int> Class::method => priority, a class gives its public methods */
    protected function layers(string $fqn, int $priority): array
    {
        if (str_contains($fqn, '::')) {
            return [$fqn => $priority];
        }

        $layers = [];
        foreach (new Reflection($fqn)->getMethods() as $method) {
            // magic methods, the constructor too, are not layers
            if ($method->isPublic() && !str_starts_with($method->name, '__')) {
                $layers["$fqn::$method->name"] = $priority;
            }
        }

        return $layers;
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
