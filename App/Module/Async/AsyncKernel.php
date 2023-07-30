<?php

namespace App\Module\Async;

use Snidget\HTTP\Request;
use Snidget\Kernel\Kernel;
use Snidget\Kernel\PSR\Event\KernelEvent;

class AsyncKernel extends Kernel
{
    public function run(?Request $request = null): never
    {
        [$router, $middlewareManager] = $this->prepare();
        $request = $this->container->get(Request::class);

        $this->eventManager->emit(KernelEvent::REQUEST, $request);

        Server::$kernelHandler = fn($request) => $this->handle($router, $middlewareManager, $request);
        Server::$request = $request;
        $scheduler = new Scheduler([
            Server::http(...),
        ], $this->container->get(Debug::class));
        $scheduler->run();
    }
}
