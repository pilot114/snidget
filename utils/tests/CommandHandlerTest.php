<?php

use PHPUnit\Framework\TestCase;
use Snidget\CLI\CommandHandler;
use Snidget\Kernel\Schema\AppPaths;

class CommandHandlerTest extends TestCase
{
    public function testParseSimpleCommand(): void
    {
        $handler = new CommandHandler(['app', 'Test:run']);
        $info = $handler->getCommandInfo([dirname(__DIR__, 2) . '/App/Module/Core/Command']);

        $this->assertNotEmpty($info);
        $this->assertStringContainsString('Test', $info[0]);
        $this->assertEquals('run', $info[1]);
    }

    public function testEmptyArgs(): void
    {
        $handler = new CommandHandler(['app']);
        $result = $handler->extractCommand([dirname(__DIR__, 2) . '/App/Module/Core/Command']);

        $this->assertEquals([], $result);
    }

    public function testInvalidFormat(): void
    {
        $handler = new CommandHandler(['app', 'invalid']);
        $info = $handler->getCommandInfo([dirname(__DIR__, 2) . '/App/Module/Core/Command']);

        $this->assertEquals([], $info);
    }

    public function testCommandNameMustMatchWholeClassName(): void
    {
        $handler = new CommandHandler(['app', 'base:run']);
        $info = $handler->getCommandInfo([dirname(__DIR__, 2) . '/App/Module/Core/Command']);

        $this->assertEquals([], $info);
    }

    public function testOptionsAndArgsAfterCommand(): void
    {
        // путь как в bin/app: с "..", раньше давал класс \Bin\..\App\...
        $paths = new AppPaths(dirname(__DIR__, 2) . '/bin/../App')->getCommandPaths();
        $handler = new CommandHandler([
            'app', 'Test:run', '-a', 'hello', '-a', 'world', '-b=test', '-de', 'one',
            '--gamma', 'test2', 'two', 'three1', 'three2',
        ]);

        [, $data] = $handler->extractCommand($paths);

        $this->assertEquals([
            'alpha' => ['hello', 'world'],
            'beta' => 'test',
            'delta' => true,
            'epsilon' => true,
            'gamma' => 'test2',
            'first' => 'one',
            'second' => 'two',
            'third' => ['three1', 'three2'],
        ], $data);
    }

    public function testIntOptionIsCast(): void
    {
        $handler = new CommandHandler(['app', 'AutoComplete:run', '-c', '2', '-i', 'app', '-i', '-a']);

        [, $data] = $handler->extractCommand([dirname(__DIR__, 2) . '/App/Module/Core/Command']);

        $this->assertSame(['current' => 2, 'input' => ['app', '-a']], $data);
    }
}
