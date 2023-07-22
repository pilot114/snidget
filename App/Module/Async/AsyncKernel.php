<?php

namespace App\Module\Async;

use Snidget\Kernel\Kernel;
use Snidget\Kernel\PSR\Event\KernelEvent;

class AsyncKernel extends Kernel
{
    public function __construct(?string $appPath = null)
    {
        parent::__construct($appPath, emitRequest: false);
    }

    public function run(): never
    {
        [$router, $middlewareManager, $request] = $this->prepare();
        $this->eventManager->emit(KernelEvent::REQUEST, $request);

        Server::$kernelHandler = fn($request) => $this->handle($router, $middlewareManager, $request);
        Server::$request = $request;
        $scheduler = new Scheduler([
            Server::http(...),
        ], $this->container->get(Debug::class));
        $scheduler->run();
    }
}
