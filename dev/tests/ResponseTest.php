<?php

use PHPUnit\Framework\TestCase;
use Snidget\HTTP\Response;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class ResponseTest extends TestCase
{
    public function testDefaultResponse(): void
    {
        $response = new Response();
        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals([], $response->getHeaders());
        self::assertEquals('', $response->getBody());
    }

    public function testWithStatus(): void
    {
        $response = new Response('Not Found');
        $new = $response->withStatus(404);

        self::assertEquals(404, $new->getStatusCode());
        self::assertEquals(200, $response->getStatusCode());
    }

    public function testWithHeader(): void
    {
        $response = new Response();
        $new = $response->withHeader('X-Custom', 'test-value');

        self::assertEquals('test-value', $new->getHeaders()['X-Custom']);
        self::assertArrayNotHasKey('X-Custom', $response->getHeaders());
    }

    public function testJsonResponse(): void
    {
        $data = ['name' => 'Alice', 'age' => 25];
        $response = Response::json($data);

        self::assertEquals(200, $response->getStatusCode());
        self::assertEquals('application/json; charset=utf-8', $response->getHeaders()['Content-Type']);
        self::assertEquals(json_encode($data, JSON_UNESCAPED_UNICODE), $response->getBody());
    }

    public function testRedirect(): void
    {
        $response = Response::redirect('/login');

        self::assertEquals(302, $response->getStatusCode());
        self::assertEquals('/login', $response->getHeaders()['Location']);
    }

    public function testResponseWithBody(): void
    {
        $response = new Response('Hello, world!', 201);

        self::assertEquals(201, $response->getStatusCode());
        self::assertEquals('Hello, world!', $response->getBody());
    }

    #[RunInSeparateProcess]
    public function testSendPrintsBody(): void
    {
        // CLI has no such function, this stub lets send() call it
        eval('function fastcgi_finish_request(): bool { return true; }');

        $this->expectOutputString('ok{"a":1}');
        new Response('ok', 201, ['X-Test' => 'yes'])->send();
        new Response('{"a":1}')->send();
    }
}
