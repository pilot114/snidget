<?php

namespace App\HTTP\Middleware;

use App\HTTP\Controller\Admin;
use Snidget\Attribute\Bind;
use Snidget\Request;
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
        return $next($request);
    }
}