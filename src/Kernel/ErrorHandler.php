<?php

namespace Snidget\Kernel;

use \Throwable;

class ErrorHandler
{
    static protected array $errors = [];

    public function onError(Throwable $exception): void
    {
        self::$errors[] = $exception;
    }

    public function onShutdown(): void
    {
        $error = error_get_last();
        if ($error) {
            self::$errors[] = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
        }

        dump($error);
        die();

//        dump(get_class($exception) . ': ' . $exception->getMessage());
//        dump($exception->getFile() . ':' . $exception->getLine());
//        dump($exception->getTraceAsString());
//        if ($this->errors && $error = error_get_last()) {
//            dump(sprintf('Fatal %s: %s', $error['type'], $error['message']));
//            dump($error['file'] . ':' . $error['line']);
//        }
    }
}