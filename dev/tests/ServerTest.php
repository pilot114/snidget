<?php

use App\Module\Async\Server;
use PHPUnit\Framework\TestCase;

class ServerTest extends TestCase
{
    public function testRequestIsCompleteWhenBodyHasContentLength(): void
    {
        self::assertFalse(Server::isComplete("GET / HTTP/1.1\r\nHost: x\r\n"));
        self::assertTrue(Server::isComplete("GET / HTTP/1.1\r\nHost: x\r\n\r\n"));
        self::assertFalse(Server::isComplete("POST / HTTP/1.1\r\ncontent-length: 5\r\n\r\n{\"a\""));
        self::assertTrue(Server::isComplete("POST / HTTP/1.1\r\ncontent-length: 5\r\n\r\n{\"a\"}"));
    }
}
