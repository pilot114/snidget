<?php

// Framework speed and memory without nginx and php-fpm: make bench

use Snidget\HTTP\Request;
use Snidget\HTTP\Response;
use Snidget\HTTP\Router;
use Snidget\Kernel\Kernel;
use Snidget\Kernel\MiddlewareManager;

$startNs = hrtime(true);
$startMemory = memory_get_usage();
include __DIR__ . '/../vendor/autoload.php';

$iterations = 500;
$coldRuns = 10;
$uris = ['', 'api/v1/post'];

$bench = static fn() => new class extends Kernel {
    /** @var array{Router, MiddlewareManager}|null */
    private ?array $prepared = null;

    public function request(string $uri): Response
    {
        [$router, $middlewares] = $this->prepared ??= $this->prepare();
        $request = new Request();
        $request->uri = $uri;

        $response = $this->handle($router, $middlewares, $request);
        return $response->getStatusCode() === 200 ? $response : exit("/$uri: {$response->getStatusCode()}\n");
    }
};

// a new process is the first request after php-fpm starts: code is not compiled yet
if (($argv[1] ?? '') === 'cold') {
    $bench()->request($argv[2] ?? '');
    echo json_encode([(hrtime(true) - $startNs) / 1e6, memory_get_peak_usage() - $startMemory]);
    exit;
}

$median = static function (float ...$values): float {
    sort($values);
    return $values[intdiv(count($values), 2)];
};

/** @param callable(): mixed $call */
$measure = static function (callable $call) use ($iterations, $median): float {
    $times = [];
    for ($i = 0; $i < $iterations; $i++) {
        $start = hrtime(true);
        $call();
        $times[] = (hrtime(true) - $start) / 1e6;
    }

    return $median(...$times);
};

$row = static function (string ...$cells): void {
    echo rtrim(implode('  ', array_map(fn(string $cell): string => mb_str_pad($cell, 18), $cells))), "\n";
};

$opcache = (string)ini_get('opcache.enable_cli');
$opcacheState = $opcache === '1' ? 'on' : 'off';
printf("Snidget: PHP %s, opcache %s, %d запросов на сценарий\n\n", PHP_VERSION, $opcacheState, $iterations);
$row('URI', 'первый запрос', 'память', 'как php-fpm', 'как Async', 'рост памяти Async');

$coldCommand = sprintf('%s -d opcache.enable_cli=%s %s cold', PHP_BINARY, $opcache, __FILE__);
foreach ($uris as $uri) {
    $cold = [];
    for ($i = 0; $i < $coldRuns; $i++) {
        /** @var array{float, int} $run time in ms, peak memory in bytes */
        $run = json_decode((string)shell_exec("$coldCommand " . escapeshellarg($uri)), true);
        $cold[] = $run;
    }

    // php-fpm builds the kernel for each request, Async builds it once
    $fpm = $measure(fn(): Response => $bench()->request($uri));
    $kernel = $bench();
    $kernel->request($uri);
    $memory = memory_get_usage();
    $async = $measure(fn(): Response => $kernel->request($uri));
    $growth = (memory_get_usage() - $memory) / $iterations;

    $row(
        "/$uri",
        sprintf('%.1f мс', $median(...array_column($cold, 0))),
        sprintf('%.0f КБ', $median(...array_column($cold, 1)) / 1024),
        sprintf('%.2f мс', $fpm),
        sprintf('%.3f мс', $async),
        sprintf('%+.0f Б/запрос', $growth),
    );
}

echo "\nпервый запрос - новый процесс, код еще не скомпилирован; память - пик за запрос\n";
echo "как php-fpm - ядро создается на каждый запрос, код уже скомпилирован (медиана)\n";
echo "как Async - одно ядро на все запросы (медиана)\n";
