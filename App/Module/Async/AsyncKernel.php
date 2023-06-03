<?php

namespace App\Module\Async;

use Snidget\Enum\SystemEvent;
use Snidget\Kernel;

class AsyncKernel extends Kernel
{
    public function __construct(?string $appPath = null)
    {
        parent::__construct($appPath, emitRequest: false);
    }

    public function run(): never
    {
        [$router, $middlewareManager, $request] = $this->prepare();
        $this->eventManager->emit(SystemEvent::REQUEST, $request);

        Server::$kernelHandler = fn($request) => $this->handle($router, $middlewareManager, $request);
        Server::$request = $request;
        $scheduler = new Scheduler([
            Server::http(...),
        ], $this->container->get(Debug::class));
        $scheduler->run();
    }
}
