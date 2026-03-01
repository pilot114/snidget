<?php

use PHPUnit\Framework\TestCase;
use Snidget\HTTP\Response;

class ResponseTest extends TestCase
{
    public function testDefaultResponse(): void
    {
        $response = new Response();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals([], $response->getHeaders());
        $this->assertEquals('', $response->getBody());
    }

    public function testWithStatus(): void
    {
        $response = new Response('Not Found');
        $new = $response->withStatus(404);

        $this->assertEquals(404, $new->getStatusCode());
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testWithHeader(): void
    {
        $response = new Response();
        $new = $response->withHeader('X-Custom', 'test-value');

        $this->assertEquals('test-value', $new->getHeaders()['X-Custom']);
        $this->assertArrayNotHasKey('X-Custom', $response->getHeaders());
    }

    public function testJsonResponse(): void
    {
        $data = ['name' => 'Alice', 'age' => 25];
        $response = Response::json($data);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json; charset=utf-8', $response->getHeaders()['Content-Type']);
        $this->assertEquals(json_encode($data, JSON_UNESCAPED_UNICODE), $response->getBody());
    }

    public function testRedirect(): void
    {
        $response = Response::redirect('/login');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('/login', $response->getHeaders()['Location']);
    }

    public function testResponseWithBody(): void
    {
        $response = new Response('Hello, world!', 201);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('Hello, world!', $response->getBody());
    }
}
