<?php

function run(bool $isAsync): never
{
    (new Snidget\Kernel())->run($isAsync);
}

function dump(mixed ...$vars): void
{
    foreach ($vars as $var) {
        $dump = print_r($var, true);
        #TODO: нужен более гибкий алгоритм. В асинхронном режиме cli, нужен вывод как для браузера
        echo php_sapi_name() === 'cli' ? "$dump\n" : "<pre>$dump</pre>";
    }
}

function autoload(string $prefix, string $baseDir): void
{
    spl_autoload_register(function ($class) use ($prefix, $baseDir) {
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

/**
 * @return iterable<string>
 */
function psrIterator(array $classPaths, bool $recursive = false): iterable
{
    foreach ($classPaths as $classPath) {
        $relPath = str_replace(\Snidget\Kernel::$appPath, 'app', $classPath);
        $parts = array_filter(explode('/', trim($relPath, '.')));
        $classNamespace = '\\' . implode('\\', array_map(ucfirst(...), $parts)) . '\\';
        foreach (glob($classPath . '/*') ?: [] as $file) {
            if ($recursive && is_dir($file)) {
                yield from psrIterator([$file], true);
                continue;
            }
            preg_match("#/(?<className>\w+)\.php#i", $file, $matches);
            if (!empty($matches['className'])) {
                yield $classNamespace . $matches['className'];
            }
        }
    }
}