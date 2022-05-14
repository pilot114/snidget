<?php

function dump(...$vars)
{
    foreach ($vars as $var) {
        echo '<pre>' . print_r($var, true) . '</pre>';
    }
}

function autoload($prefix, $baseDir)
{
    spl_autoload_register(function($class) use ($prefix, $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

function psrIterator(string $controllerPath): iterable
{
    $parts = array_filter(explode('/', trim($controllerPath, '.')));
    $controllerNamespace = '\\' . implode('\\', array_map(ucfirst(...), $parts)) . '\\';
    foreach (glob($controllerPath . '/*') as $controller) {
        preg_match("#/(?<className>\w+)\.php#i", $controller, $matches);
        yield $controllerNamespace . $matches['className'];
    }
}

function errorHandler()
{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    set_error_handler(function ($code, $message, $file = null, $line = null) {
        dump(sprintf('error (%s): %s', $code, $message));
        dump($file . ':' . $line);
    });
    set_exception_handler(function (Throwable $exception) {
        dump('Throwable: ' . $exception->getMessage());
        dump($exception->getFile() . ':' . $exception->getLine());
        dump($exception->getTraceAsString());
    });
}

autoload('Snidget\\', __DIR__ . '/../src/');
autoload('App\\', __DIR__ . '/../app/');
errorHandler();