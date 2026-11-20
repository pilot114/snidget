<?php

namespace Snidget\HTTP;

use Snidget\Kernel\SnidgetException;

class Router
{
    /** @var list<array{regex: string, fqn: string, method: string}> */
    protected array $routes = [];

    public function register(string $regex, string $fqn, string $method = 'GET'): void
    {
        $this->routes[] = [
            'regex' => $regex,
            'fqn' => $fqn,
            'method' => strtoupper($method),
        ];
    }

    /** @return list<array{regex: string, fqn: string, method: string}> */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * @return array{string, string, array<string, string>} контроллер, действие и именованные группы из URI
     * @throws SnidgetException
     */
    public function match(Request $request): array
    {
        $uriMatched = false;

        foreach ($this->routes as $route) {
            if (preg_match("#^{$route['regex']}$#i", $request->uri, $matches) === 1) {
                $uriMatched = true;
                if (strtoupper($request->method) !== $route['method']) {
                    continue;
                }

                [$controller, $action] = explode('::', $route['fqn']);
                return [$controller, $action, array_filter($matches, is_string(...), ARRAY_FILTER_USE_KEY)];
            }
        }

        if ($uriMatched) {
            throw new SnidgetException("Метод {$request->method} не поддерживается для URI: '{$request->uri}'", 405);
        }

        throw new SnidgetException("Не найден роут для URI: '$request->uri'", 404);
    }
}
