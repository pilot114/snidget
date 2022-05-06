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

function errorHandler()
{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    set_error_handler(function ($code, $message, $file = null, $line = null) {
        dump($code);
        dump($message);
        dump($file);
        dump($line);
    });
    set_exception_handler(function ($exception) {
        dump($exception);
    });
}