<?php

namespace App\Box\Core\HTTP\Middleware;

use Snidget\Attribute\Bind;
use Snidget\{DTO\Config\App, Duck, Request};
use Closure;

#[Bind(priority: PHP_INT_MAX)]
class BuiltIn
{
    public function auth(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function duckValidate(Request $request, Closure $next, App $config)
    {
        if ($request->payload) {
            $duck = new Duck($config->getDtoPaths());
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