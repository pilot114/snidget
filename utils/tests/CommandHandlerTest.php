<?php

use PHPUnit\Framework\TestCase;
use Snidget\CLI\CommandHandler;

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
}
