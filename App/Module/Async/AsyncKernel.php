<?php

namespace App\Module\Async;

use Snidget\HTTP\Request;
use Snidget\Kernel\Kernel;

class AsyncKernel extends Kernel
{
    #[\Override]
    public function run(?Request $request = null): never
    {
        [$router, $middlewareManager] = $this->prepare();

        Server::$kernelHandler = $this->handle($router, $middlewareManager, ?);
        $scheduler = new Scheduler([
            Server::http(...),
        ], $this->container->get(Debug::class));
        $scheduler->run();
    }
}
