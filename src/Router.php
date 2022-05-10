<?php

namespace Snidget;

class Router
{
    protected array $routes = [];
    protected array $route = [];

    public function register(string $regex, string $fqdn): void
    {
        $this->routes[$regex] = $fqdn;
    }

    public function routes(): array
    {
        return $this->routes;
    }

    public function route(): array
    {
        return $this->route;
    }

    public function match(Request $request): ?array
    {
        foreach ($this->routes as $pattern => $fqdn) {
            if (preg_match("#^$pattern$#i", $request->getUri(), $matches)) {
                $names = array_filter(array_keys($matches), is_string(...));
                $matchesNamed = array_filter($matches, fn($x) => in_array($x, $names), ARRAY_FILTER_USE_KEY);
                $this->route = [...explode('::', $fqdn), $matchesNamed];
                return $this->route;
            }
        }
        return null;
    }
}