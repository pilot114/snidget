<?php

namespace Snidget\Kernel;

use Snidget\Kernel\PSR\Event\KernelEvent;
use Snidget\Kernel\PSR\Event\Listen;
use Throwable;

class ErrorHandler
{
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
        if ($error) {
            self::$errors[] = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
        }

        foreach (self::$errors as $exception) {
            dump($exception::class . ': ' . $exception->getMessage());
            dump($exception->getFile() . ':' . $exception->getLine());
            dump($exception->getTraceAsString());
        }
    }
}
