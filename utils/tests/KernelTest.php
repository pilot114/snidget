<?php

use PHPUnit\Framework\TestCase;
use Snidget\Kernel\Schema\AppPaths;

class KernelTest extends TestCase
{
    public function testKernelCreatesWithDynamicPaths(): void
    {
        $appPath = dirname(__DIR__, 2) . '/App';
        $this->assertDirectoryExists($appPath);
    }

    public function testAppPathsResolvesExistingDirs(): void
    {
        $appPath = dirname(__DIR__, 2) . '/App';
        $paths = new AppPaths($appPath);

        $controllerPaths = $paths->getControllerPaths();
        $commandPaths = $paths->getCommandPaths();

        $this->assertNotEmpty($controllerPaths);
        $this->assertNotEmpty($commandPaths);

        $existingPaths = array_filter($controllerPaths, 'is_dir');
        $this->assertNotEmpty($existingPaths, 'Должны быть найдены директории контроллеров');

        $existingCommands = array_filter($commandPaths, 'is_dir');
        $this->assertNotEmpty($existingCommands, 'Должны быть найдены директории команд');
    }
}
