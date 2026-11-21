<?php

use PHPUnit\Framework\TestCase;
use Snidget\HTTP\Request;
use Snidget\HTTP\Response;
use Snidget\Kernel\Kernel;
use Snidget\Kernel\Schema\AppPaths;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Snidget\HTTP\Router;
use Snidget\Kernel\MiddlewareManager;
use Snidget\Tests\Fixtures\FixtureController;

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

    public function testHandleTurnsResultsAndErrorsIntoResponses(): void
    {
        include_once __DIR__ . '/fixtures/Handlers.php';
        $kernel = new class extends Kernel {
            public function request(Router $router, string $uri): Response
            {
                $request = new Request();
                $request->uri = $uri;
                return $this->handle($router, new MiddlewareManager([], $this->container), $request);
            }
        };
        restore_error_handler();
        restore_exception_handler();

        $router = new Router();
        $router->register('arr', FixtureController::class . '::arr');
        $router->register('boom', FixtureController::class . '::boom');

        self::assertSame('{"a":1}', $kernel->request($router, 'arr')->getBody());
        self::assertSame(404, $kernel->request($router, 'nope')->getStatusCode());
        self::assertSame(500, $kernel->request($router, 'boom')->getStatusCode());
    }

    #[RunInSeparateProcess]
    public function testErrorAndExceptionHandlers(): void
    {
        new Kernel();
        $onError = set_error_handler(null) ?? self::fail('no error handler');
        $onException = set_exception_handler(null) ?? self::fail('no exception handler');
        // drop the null handlers we just set and the Kernel ones, PHPUnit wants its own back
        restore_error_handler();
        restore_error_handler();
        restore_exception_handler();
        restore_exception_handler();

        self::assertTrue($onError(E_USER_WARNING, 'warning', __FILE__, __LINE__));
        $level = error_reporting(0);
        self::assertFalse($onError(E_USER_WARNING, 'suppressed', __FILE__, __LINE__));
        error_reporting($level);

        $onException(new RuntimeException('uncaught'));
        self::assertSame(500, http_response_code());
    }

    #[RunInSeparateProcess]
    public function testRunSendsResponse(): void
    {
        $request = new Request();
        $request->uri = '';

        $kernel = new Kernel();
        restore_error_handler();
        restore_exception_handler();

        $this->expectOutputString('main');
        $kernel->run($request);
    }
}
