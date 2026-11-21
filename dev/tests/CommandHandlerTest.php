<?php

use App\Module\Core\Command\Test;
use App\Module\Core\Schema\Command\TestInput;
use PHPUnit\Framework\TestCase;
use Snidget\CLI\CommandHandler;
use Snidget\Kernel\Schema\AppPaths;
use App\Module\Core\Command\Database;
use Dev\Tests\Fixtures\Command\Fixture;
use Snidget\Kernel\SnidgetException;

class CommandHandlerTest extends TestCase
{
    public function testParseSimpleCommand(): void
    {
        $handler = new CommandHandler(['app', 'Test:run']);
        $info = $handler->getCommandInfo([dirname(__DIR__, 2) . '/App/Module/Core/Command']);

        self::assertSame([Test::class, 'run', TestInput::class], $info);
    }

    public function testEmptyArgs(): void
    {
        $handler = new CommandHandler(['app']);
        $result = $handler->extractCommand([dirname(__DIR__, 2) . '/App/Module/Core/Command']);

        self::assertEquals([], $result);
    }

    public function testInvalidFormat(): void
    {
        $handler = new CommandHandler(['app', 'invalid']);
        $info = $handler->getCommandInfo([dirname(__DIR__, 2) . '/App/Module/Core/Command']);

        self::assertEquals([], $info);
    }

    public function testCommandNameMustMatchWholeClassName(): void
    {
        $handler = new CommandHandler(['app', 'base:run']);
        $info = $handler->getCommandInfo([dirname(__DIR__, 2) . '/App/Module/Core/Command']);

        self::assertEquals([], $info);
    }

    public function testOptionsAndArgsAfterCommand(): void
    {
        // путь как в bin/app: с "..", раньше давал класс \Bin\..\App\...
        $paths = new AppPaths(dirname(__DIR__, 2) . '/bin/../App')->getCommandPaths();
        $handler = new CommandHandler([
            'app', 'Test:run', '-a', 'hello', '-a', 'world', '-b=test', '-de', 'one',
            '--gamma', 'test2', 'two', 'three1', 'three2',
        ]);

        $data = $handler->extractCommand($paths)[1] ?? null;

        self::assertEquals([
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

        $data = $handler->extractCommand([dirname(__DIR__, 2) . '/App/Module/Core/Command'])[1] ?? null;

        self::assertSame(['current' => 2, 'input' => ['app', '-a']], $data);
    }

    public function testCommandWithoutDto(): void
    {
        $handler = new CommandHandler(['app', 'Database:run']);
        $result = $handler->extractCommand([dirname(__DIR__, 2) . '/App/Module/Core/Command']);

        self::assertSame([null, null, Database::class, 'run'], $result);
    }

    public function testDefaultListsCommands(): void
    {
        $this->expectOutputRegex('/^AutoComplete\n    run\tauto-complete for commands and options\n/');
        new CommandHandler(['app'])->default([dirname(__DIR__, 2) . '/App/Module/Core/Command']);
    }

    public function testFloatOptionAndArgsAfterDoubleDash(): void
    {
        include_once __DIR__ . '/fixtures/Command/Fixture.php';
        $handler = new CommandHandler(['app', 'Fixture:floats', '--ratio', '1.5', '--', '-x', 'y']);
        $result = $handler->extractCommand([__DIR__ . '/fixtures/Command']);

        self::assertSame(Fixture::class, $result[2] ?? null);
        self::assertSame(['ratio' => 1.5, 'rest' => ['-x', 'y']], $result[1] ?? null);
    }

    public function testOptionMustBeNullable(): void
    {
        include_once __DIR__ . '/fixtures/Command/Fixture.php';
        $this->expectException(SnidgetException::class);
        new CommandHandler(['app', 'Fixture:strict'])->extractCommand([__DIR__ . '/fixtures/Command']);
    }
}
