<?php

namespace Snidget\Kernel;

use Snidget\Kernel\PSR\Event\KernelEvent;
use Snidget\Kernel\PSR\Event\Listen;
use Throwable;

class ErrorHandler
{
    /** @var list<Throwable> */
    protected static array $errors = [];

    #[Listen(KernelEvent::ERROR)]
    public function onError(Throwable $data): void
    {
        self::$errors[] = $data;
    }

    #[Listen(KernelEvent::FINISH)]
    public function onShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null) {
            self::$errors[] = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
        }

        // PHP log or stderr, never the response: a trace shows server internals
        foreach (self::$errors as $exception) {
            error_log((string)$exception);
        }
    }
}
