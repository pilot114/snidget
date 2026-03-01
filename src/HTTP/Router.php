<?php

namespace Snidget\HTTP;

use Snidget\Kernel\SnidgetException;

class Router
{
    protected array $routes = [];
    protected array $route = [];

    public function register(string $regex, string $fqn, string $method = 'GET'): void
    {
        $this->routes[] = [
            'regex' => $regex,
            'fqn' => $fqn,
            'method' => strtoupper($method),
        ];
    }

    public function routes(): array
    {
        return $this->routes;
    }

    public function match(Request $request): array
    {
        $uriMatched = false;

        foreach ($this->routes as $route) {
            if (preg_match("#^{$route['regex']}$#i", $request->uri, $matches)) {
                $uriMatched = true;
                if (strtoupper($request->method) !== $route['method']) {
                    continue;
                }
                $names = array_filter(array_keys($matches), is_string(...));
                $matchesNamed = array_filter($matches, fn($x): bool => in_array($x, $names), ARRAY_FILTER_USE_KEY);
                return $this->route = [...explode('::', $route['fqn']), $matchesNamed];
            }
        }

        if ($uriMatched) {
            throw new SnidgetException("Метод {$request->method} не поддерживается для URI: '{$request->uri}'", 405);
        }
        throw new SnidgetException("Не найден роут для URI: '$request->uri'", 404);
    }
}
