<?php

namespace SnidgetAI;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

interface KernelInterface extends RequestHandlerInterface
{
    public function addMiddleware(MiddlewareInterface $middleware): void;
    public function run(): void;
}
