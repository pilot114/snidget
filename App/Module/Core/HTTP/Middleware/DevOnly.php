<?php

namespace App\Module\Core\HTTP\Middleware;

use App\Module\Core\HTTP\Controller\Admin;
use Closure;
use Snidget\HTTP\Bind;
use Snidget\HTTP\Request;
use Snidget\HTTP\Response;

// the admin shows how the app is built, so it works only in development
#[Bind(class: Admin::class)]
class DevOnly
{
    /**
     * @param Closure(Request): mixed $next
     */
    public function check(Request $request, Closure $next): mixed
    {
        return getenv('APP_ENV') === 'dev' ? $next($request) : new Response('Not Found', 404);
    }
}
