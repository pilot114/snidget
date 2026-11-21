<?php

namespace Snidget\Kernel;

use Snidget\Kernel\PSR\Event\KernelEvent;
use Snidget\Kernel\PSR\Event\Listen;
use Throwable;

class ErrorHandler
{
    // PHP log or stderr, never the response: a trace shows server internals
    #[Listen(KernelEvent::ERROR)]
    public function onError(Throwable $data): void
    {
        error_log((string)$data);
    }
}
