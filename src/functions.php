<?php

use Snidget\HTTP\Request;
use Snidget\Kernel\Kernel;

function request(string $url, string $method, array $data): never
{
    $request = new Request();
    $request->uri = $url;
    $request->method = $method;
    $request->payload = $data;
    (new Kernel())->run($request);
}

function isCli(): bool
{
    return php_sapi_name() === 'cli';
}

function dump(mixed ...$vars): void
{
    foreach ($vars as $var) {
        $dump = print_r($var, true);
        #TODO: В асинхронном режиме вывод не работает
        echo isCli() ? "$dump\n" : "<pre>$dump</pre>";
    }
}

function autoload(string $prefix, string $baseDir): void
{
    spl_autoload_register(function ($class) use ($prefix, $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $file = $baseDir . str_replace('\\', '/', substr($class, $len)) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    });
}

/**
 * Получаем все классы в директории, если они соотвествуют PSR
 *
 * @return Generator<string>
 */
function psrIterator(array $classPaths, bool $recursive = false, ?string $alias = null): \Generator
{
    foreach ($classPaths as $classPath) {
        $relPath = substr($classPath, strlen(dirname(__DIR__)) + 1);
        $parts = array_filter(explode('/', $relPath));
        if ($alias) {
            $parts[0] = $alias;
        }
        $classNamespace = '\\' . implode('\\', array_map(ucfirst(...), $parts)) . '\\';

        foreach (glob($classPath . '/*') ?: [] as $file) {
            if ($recursive && is_dir($file)) {
                yield from psrIterator([$file], true, $alias);
                continue;
            }
            preg_match("#/(?<className>\w+)\.php#i", $file, $matches);
            if (!empty($matches['className'])) {
                yield $classNamespace . $matches['className'];
            }
        }
    }
}

/**
 * Вывод миллисекунд в удобочитаемом формате
 */
function millisecondPrint(int $sec): string
{
    $ms = $sec % 1_000;
    $s = floor(($sec % 60_000) / 1_000);
    $m = floor(($sec % 3_600_000) / 60_000);
    $h = floor(($sec % 86_400_000) / 3_600_000);
    $d = floor(($sec % 2_592_000_000) / 86_400_000);
    $M = floor($sec / 2_592_000_000);
    $result = [];
    $M && $result[] = "$M months";
    $d && $result[] = "$d days";
    $h && $result[] = "$h hours";
    $m && $result[] = "$m minutes";
    $s && $result[] = "$s sec";
    $ms && $result[] = "$ms msec";

    return implode(' ', $result);
}
