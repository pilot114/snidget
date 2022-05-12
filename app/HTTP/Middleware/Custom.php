<?php

namespace App\HTTP\Middleware;

use App\HTTP\Controller\Example;
use Snidget\Attribute\Bind;
use Snidget\Request;
use Closure;

class Custom
{
    #[Bind(class: Example::class, method: 'get')]
    public function echoWelcome(Request $request, Closure $next)
    {
        echo 'Welcome before!';
        $response = $next($request);
        echo 'Welcome after!';
        return $response;
    }

    public function back(Request $request, Closure $next)
    {
        echo 'Back call before!';
        $response = $next($request);
        echo 'Back call after!';
        return $response;
    }
}