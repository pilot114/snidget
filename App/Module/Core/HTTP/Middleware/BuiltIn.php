<?php

namespace App\Module\Core\HTTP\Middleware;

use App\Module\Core\Domain\Duck;
use Closure;
use Snidget\{HTTP\Request, Kernel\Schema\AppPaths};
use Snidget\HTTP\Bind;

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
