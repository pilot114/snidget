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
