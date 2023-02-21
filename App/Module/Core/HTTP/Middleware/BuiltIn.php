<?php

namespace App\Module\Core\HTTP\Middleware;

use App\Module\Core\Domain\Duck;
use Snidget\Attribute\Bind;
use Snidget\{Schema\Config\AppPaths, Request};
use Closure;

#[Bind(priority: PHP_INT_MAX)]
class BuiltIn
{
    public function auth(Request $request, Closure $next): mixed
    {
        return $next($request);
    }

    public function duckValidate(Request $request, Closure $next, AppPaths $config): mixed
    {
        if ($request->payload) {
            $duck = new Duck($config->getSchemaPaths());
            $messages = [];
            foreach ($duck->layAnEgg($request->payload) as $name => $errors) {
                $messages[] = sprintf("Поле %s не прошло валидацию: %s", $name, implode('|', $errors));
            }
            if ($messages) {
                dump($messages);
                die();
            }
        }

        return $next($request);
    }
}