<?php

function isCli(): bool
{
    return php_sapi_name() === 'cli';
}

function dump(mixed ...$vars): void
{
    foreach ($vars as $var) {
        $dump = print_r($var, true);
        echo isCli() ? "$dump\n" : '<pre>' . htmlspecialchars($dump) . '</pre>';
    }
}

/**
 * Class names of PSR-4 files in the given dirs
 *
 * @param list<string> $classPaths
 * @return Generator<string>
 */
function psrIterator(array $classPaths, bool $recursive = false, ?string $alias = null): \Generator
{
    foreach ($classPaths as $classPath) {
        // else bin/../App becomes namespace \Bin\..\App
        $classPath = realpath($classPath);
        if ($classPath === false) {
            continue;
        }

        $relPath = substr($classPath, strlen(dirname(__DIR__)) + 1);
        $parts = array_filter(explode('/', $relPath), fn(string $part): bool => $part !== '');
        if ($alias !== null) {
            $parts[0] = $alias;
        }
        $classNamespace = '\\' . implode('\\', array_map(ucfirst(...), $parts)) . '\\';

        $files = glob($classPath . '/*');
        foreach ($files === false ? [] : $files as $file) {
            if ($recursive && is_dir($file)) {
                yield from psrIterator([$file], true, $alias);
                continue;
            }
            if (preg_match("#/(?<className>\w+)\.php$#", $file, $matches) === 1) {
                yield $classNamespace . $matches['className'];
            }
        }
    }
}

function millisecondPrint(int $ms): string
{
    $units = array_filter([
        'months'  => intdiv($ms, 2_592_000_000),
        'days'    => intdiv($ms % 2_592_000_000, 86_400_000),
        'hours'   => intdiv($ms % 86_400_000, 3_600_000),
        'minutes' => intdiv($ms % 3_600_000, 60_000),
        'sec'     => intdiv($ms % 60_000, 1_000),
        'msec'    => $ms % 1_000,
    ], fn(int $value): bool => $value !== 0);

    return implode(' ', array_map(fn(int $value, string $unit): string => "$value $unit", $units, array_keys($units)));
}
