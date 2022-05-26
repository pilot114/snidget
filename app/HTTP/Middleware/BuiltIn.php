<?php

namespace App\HTTP\Middleware;

use Snidget\Attribute\Bind;
use Snidget\{Duck, Request};
use Closure;

#[Bind(priority: PHP_INT_MAX)]
class BuiltIn
{
    public function auth(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function validate(Request $request, Closure $next)
    {
        if ($request->data) {
            $duck = new Duck('../app/DTO/API');
            $messages = [];
            foreach ($duck->layAnEgg($request->data) as $name => $errors) {
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