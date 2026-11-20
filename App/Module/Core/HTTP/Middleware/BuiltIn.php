<?php

namespace App\Module\Core\HTTP\Middleware;

use App\Module\Core\Domain\Duck;
use Closure;
use Snidget\{HTTP\Request, HTTP\Response, Kernel\Schema\AppPaths};
use Snidget\HTTP\Bind;

#[Bind(priority: PHP_INT_MAX)]
class BuiltIn
{
    /**
     * @param Closure(Request): mixed $next
     */
    public function auth(Request $request, Closure $next): mixed
    {
        return $next($request);
    }

    /**
     * @param Closure(Request): mixed $next
     */
    public function duckValidate(Request $request, Closure $next, AppPaths $config): mixed
    {
        if ($request->payload !== null && $request->payload !== []) {
            if (!is_array($request->payload)) {
                return Response::json(['errors' => ['Ожидается JSON-объект']], 422);
            }

            $duck = new Duck($config->getSchemaPaths());
            $messages = [];
            foreach ($duck->layAnEgg($request->payload) as $name => $errors) {
                $messages[] = sprintf("Поле %s не прошло валидацию: %s", $name, implode('|', $errors));
            }

            if ($messages !== []) {
                return Response::json(['errors' => $messages], 422);
            }
        }

        return $next($request);
    }
}
