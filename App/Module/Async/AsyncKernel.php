<?php

namespace App\Module\Async;

use Snidget\HTTP\Request;
use Snidget\HTTP\Response;
use Snidget\Kernel\Kernel;
use Snidget\Kernel\PSR\Log\Logger;

class AsyncKernel extends Kernel
{
    #[\Override]
    public function run(?Request $request = null): never
    {
        [$router, $middlewareManager] = $this->prepare();

        Server::$kernelHandler = function (Request $request) use ($router, $middlewareManager): Response {
            // the process lives long, so the log lives one request, as under php-fpm
            $this->container->set(Logger::class, new Logger());
            return $this->handle($router, $middlewareManager, $request);
        };
        $scheduler = new Scheduler([
            Server::http(...),
        ], $this->container->get(Debug::class));
        $scheduler->run();
    }
}
