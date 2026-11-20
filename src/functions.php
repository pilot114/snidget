<?php

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

/**
 * Получаем все классы в директории, если они соотвествуют PSR
 *
 * @param list<string> $classPaths
 * @return Generator<string>
 */
function psrIterator(array $classPaths, bool $recursive = false, ?string $alias = null): \Generator
{
    foreach ($classPaths as $classPath) {
        // путь вида bin/../App иначе превращается в неймспейс \Bin\..\App
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
            if (preg_match("#/(?<className>\w+)\.php#i", $file, $matches) === 1) {
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
    $units = [
        'months'  => intdiv($sec, 2_592_000_000),
        'days'    => intdiv($sec % 2_592_000_000, 86_400_000),
        'hours'   => intdiv($sec % 86_400_000, 3_600_000),
        'minutes' => intdiv($sec % 3_600_000, 60_000),
        'sec'     => intdiv($sec % 60_000, 1_000),
        'msec'    => $sec % 1_000,
    ];

    $result = [];
    foreach ($units as $unit => $value) {
        if ($value !== 0) {
            $result[] = "$value $unit";
        }
    }

    return implode(' ', $result);
}
