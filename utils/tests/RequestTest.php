<?php

use PHPUnit\Framework\TestCase;
use Snidget\HTTP\Request;

class RequestTest extends TestCase
{
    public function testFromString(): void
    {
        $request = new Request();
        $request->fromString("GET /test HTTP/1.1\nHost: localhost\n\n", 1000000.0);

        $this->assertEquals('test', $request->uri);
        $this->assertEquals('GET', $request->method);
        $this->assertArrayHasKey('HOST', $request->headers);
    }

    public function testFromStringWithBody(): void
    {
        $body = json_encode(['key' => 'value']);
        $request = new Request();
        $request->fromString("POST /api HTTP/1.1\nContent-Type: application/json\n\n$body", 1000000.0);

        $this->assertEquals('api', $request->uri);
        $this->assertEquals('POST', $request->method);
        $this->assertEquals(['key' => 'value'], $request->payload);
    }

    public function testFromStringRealHttp(): void
    {
        $request = new Request();
        $request->fromString(
            "POST /api/v1/post?page=2 HTTP/1.1\r\nHost: a\r\nUser-Agent: curl\r\n\r\n{\"login\":\"abcd\"}",
            0
        );

        $this->assertEquals('api/v1/post', $request->uri);
        $this->assertEquals(['page' => '2'], $request->query);
        $this->assertEquals(['login' => 'abcd'], $request->payload);
        $this->assertEquals('curl', $request->getHeader('User-Agent'));
    }

    public function testFromStringWithoutBody(): void
    {
        $request = new Request();
        $request->fromString("GET / HTTP/1.1\r\nHost: a\r\n\r\n", 0);

        $this->assertNull($request->payload);
    }

    public function testFromGlobalHeaderNames(): void
    {
        $_SERVER['REQUEST_URI'] = '/x';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_USER_AGENT'] = 'curl';
        $_SERVER['CONTENT_TYPE'] = 'application/json';

        $request = new Request()->fromGlobal();
        unset($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
        unset($_SERVER['HTTP_USER_AGENT'], $_SERVER['CONTENT_TYPE']);

        $this->assertEquals('curl', $request->getHeader('User-Agent'));
        $this->assertEquals('application/json', $request->getHeader('Content-Type'));
    }

    public function testGetHeader(): void
    {
        $request = new Request();
        $request->fromString("GET /test HTTP/1.1\nHost: localhost\nContent-Type: text/html\n\n", 1000000.0);

        $this->assertEquals('localhost', trim($request->getHeader('HOST')));
        $this->assertNull($request->getHeader('X-NONEXISTENT'));
    }

    public function testMethodParsing(): void
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE', 'PATCH'] as $method) {
            $request = new Request();
            $request->fromString("$method /test HTTP/1.1\n\n", 1000000.0);
            $this->assertEquals($method, $request->method);
        }
    }
}
