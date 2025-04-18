<?php

namespace Snidget\HTTP;

class Request
{
    public string $uri;
    public string $method = 'GET';
    public array $headers = [];
    public mixed $payload;
    public float $requestTimeMs;

    public function fromGlobal(): self
    {
        $this->uri = trim(parse_url($_SERVER['REQUEST_URI'])['path'] ?? '', '/');
        $this->payload = json_decode(file_get_contents('php://input') ?: '', true);
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->requestTimeMs = $_SERVER['REQUEST_TIME_FLOAT'];

        $http = array_filter($_SERVER, fn($key): bool => str_starts_with($key, 'HTTP_'), ARRAY_FILTER_USE_KEY);
        foreach ($http as $headerName => $header) {
            $this->headers[str_replace('HTTP_', '', $headerName)] = $header;
        }
        return $this;
    }

    public function fromString(string $request, float $startTimeNs): self
    {
        [$headers, $body] = str_contains($request, "\n\n") ? explode("\n\n", $request) : [$request, ''];
        if ($body !== '' && $body !== '0') {
            $this->payload = json_decode($body, true);
        }
        $this->requestTimeMs = round($startTimeNs / 1_000_000, 4);
        $this->parseHeaders($headers);
        return $this;
    }

    protected function parseHeaders(string $headers): void
    {
        $headers = array_filter(explode("\n", $headers), fn($line): bool => trim($line) !== '');
        [$this->method, $uri] = explode(' ', array_shift($headers) ?? '');
        $this->uri = trim($uri, '/');

        foreach ($headers as $header) {
            $header = explode(':', $header);
            $headerName = strtoupper(array_shift($header));
            $this->headers[$headerName] = implode(':', $header);
        }
    }
}
