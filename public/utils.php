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