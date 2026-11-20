<?php

use PHPUnit\Framework\TestCase;
use Snidget\HTTP\Request;
use Snidget\HTTP\Response;
use Snidget\Kernel\Kernel;
use Snidget\Kernel\Schema\AppPaths;

class KernelTest extends TestCase
{
    public function testKernelCreatesWithDynamicPaths(): void
    {
        $appPath = dirname(__DIR__, 2) . '/App';
        self::assertDirectoryExists($appPath);
    }

    public function testAppPathsResolvesExistingDirs(): void
    {
        $appPath = dirname(__DIR__, 2) . '/App';
        $paths = new AppPaths($appPath);

        $controllerPaths = $paths->getControllerPaths();
        $commandPaths = $paths->getCommandPaths();

        self::assertNotEmpty($controllerPaths);
        self::assertNotEmpty($commandPaths);

        $existingPaths = array_filter($controllerPaths, is_dir(...));
        self::assertNotEmpty($existingPaths, 'Должны быть найдены директории контроллеров');

        $existingCommands = array_filter($commandPaths, is_dir(...));
        self::assertNotEmpty($existingCommands, 'Должны быть найдены директории команд');
    }

    public function testHandleReturnsStatusInsteadOfTrace(): void
    {
        $kernel = new class extends Kernel {
            public function request(string $method, string $uri): Response
            {
                $request = new Request();
                $request->method = $method;
                $request->uri = $uri;
                [$router, $middlewareManager] = $this->prepare();
                return $this->handle($router, $middlewareManager, $request);
            }
        };
        restore_error_handler();
        restore_exception_handler();

        self::assertSame(404, $kernel->request('GET', 'nope')->getStatusCode());

        $response = $kernel->request('POST', 'nope');
        self::assertSame(405, $response->getStatusCode());
        self::assertSame('Method Not Allowed', $response->getBody());

        self::assertSame(200, $kernel->request('GET', 'admin/routes')->getStatusCode());
    }
}
