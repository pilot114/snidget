<?php

namespace Dev\Tests\Fixtures\Middleware;

use Closure;
use Snidget\HTTP\Bind;
use Snidget\HTTP\Request;
use Snidget\Tests\Fixtures\FixtureController;

#[Bind(class: FixtureController::class, method: 'one')]
#[Bind(class: FixtureController::class, method: 'two')]
class TwiceMiddleware
{
    public function __construct()
    {
    }

    /** @param Closure(Request): mixed $next */
    public function mark(Request $request, Closure $next): mixed
    {
        return $this->helper($next($request));
    }

    protected function helper(mixed $result): mixed
    {
        return $result;
    }
}
