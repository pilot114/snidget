<?php

namespace Snidget\HTTP;

class Request
{
    public string $uri = '';

    public string $method = 'GET';

    /** @var array<string, string> */
    public array $headers = [];

    public mixed $payload = null;

    public float $requestTimeMs = 0.0;

    /** @var array<mixed> */
    public array $query = [];

    /** @var array<mixed> */
    public array $cookies = [];

    public function fromGlobal(): self
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $this->uri = trim((string)parse_url(is_string($requestUri) ? $requestUri : '', PHP_URL_PATH), '/');
        $input = file_get_contents('php://input');
        $this->payload = json_decode($input === false ? '' : $input, true);
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        $this->method = is_string($method) ? $method : 'GET';
        $time = $_SERVER['REQUEST_TIME_FLOAT'] ?? null;
        $this->requestTimeMs = is_float($time) ? $time : microtime(true);
        $this->query = $_GET;
        $this->cookies = $_COOKIE;

        // имена как в fromString: USER-AGENT; CONTENT_* приходят без префикса HTTP_
        foreach ($_SERVER as $key => $value) {
            $key = (string)$key;
            if (!is_string($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $key = substr($key, 5);
            } elseif ($key !== 'CONTENT_TYPE' && $key !== 'CONTENT_LENGTH') {
                continue;
            }

            $this->headers[str_replace('_', '-', $key)] = $value;
        }

        return $this;
    }

    public function fromString(string $request, float $startTimeNs): self
    {
        $parts = preg_split('#\r?\n\r?\n#', $request, 2);
        [$headers, $body] = array_pad($parts === false ? [] : $parts, 2, '');
        if ($body !== '' && $body !== '0') {
            $this->payload = json_decode($body, true);
        }

        $this->requestTimeMs = round($startTimeNs / 1_000_000, 4);
        $this->parseHeaders($headers);
        return $this;
    }

    public function getHeader(string $name): ?string
    {
        $name = strtoupper($name);
        return $this->headers[$name] ?? null;
    }

    protected function parseHeaders(string $headers): void
    {
        $headers = array_filter(explode("\n", $headers), fn($line): bool => trim($line) !== '');
        [$this->method, $target] = array_pad(explode(' ', trim(array_shift($headers) ?? '')), 2, '');
        $this->uri = trim((string)parse_url($target, PHP_URL_PATH), '/');
        parse_str((string)parse_url($target, PHP_URL_QUERY), $this->query);

        foreach ($headers as $header) {
            $header = explode(':', $header);
            $headerName = strtoupper(array_shift($header));
            $this->headers[$headerName] = trim(implode(':', $header));
        }
    }
}
