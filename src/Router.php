<?php

namespace Wshell\Snidget;

class Router
{
    protected array $routes = [];

    public function register(string $regex, string $controllerName, string $actionName)
    {
        $this->routes[$regex] = [$controllerName, $actionName];
    }

    public function all(): array
    {
        return $this->routes;
    }

    public function match(Request $request): ?array
    {
        foreach ($this->routes as $pattern => $route) {
            if (preg_match("#^$pattern$#i", $request->getUri(), $matches)) {
                $names = array_filter(array_keys($matches), is_string(...));
                $matchesNamed = array_filter($matches, fn($x) => in_array($x, $names), ARRAY_FILTER_USE_KEY);
                $route[] = $matchesNamed;
                return $route;
            }
        }
        return null;
    }
}