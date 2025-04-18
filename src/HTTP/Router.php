<?php

namespace Snidget\HTTP;

use Snidget\Kernel\SnidgetException;

class Router
{
    protected array $routes = [];
    protected array $route = [];

    public function register(string $regex, string $fqn): void
    {
        $this->routes[$regex] = $fqn;
    }

    public function routes(): array
    {
        return $this->routes;
    }

    public function match(Request $request): array
    {
        foreach ($this->routes as $pattern => $fqn) {
            if (preg_match("#^$pattern$#i", $request->uri, $matches)) {
                $names = array_filter(array_keys($matches), is_string(...));
                $matchesNamed = array_filter($matches, fn($x): bool => in_array($x, $names), ARRAY_FILTER_USE_KEY);
                return $this->route = [...explode('::', $fqn), $matchesNamed];
            }
        }
        throw new SnidgetException("Не найден роут для URI: '$request->uri'");
    }
}
