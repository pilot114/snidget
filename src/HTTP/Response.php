<?php

namespace Snidget\HTTP;

class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        protected string $body = '',
        protected int $statusCode = 200,
        protected array $headers = [],
    ) {
    }

    #[\NoDiscard('Response неизменяемый, изменения — в возвращённой копии')]
    public function withStatus(int $code): self
    {
        return clone($this, ['statusCode' => $code]);
    }

    #[\NoDiscard('Response неизменяемый, изменения — в возвращённой копии')]
    public function withHeader(string $name, string $value): self
    {
        return clone($this, ['headers' => [...$this->headers, $name => $value]]);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** @return array<string, string> */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public static function json(mixed $data, int $statusCode = 200): self
    {
        return new self(
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            $statusCode,
            ['Content-Type' => 'application/json; charset=utf-8']
        );
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        return new self('', $statusCode, ['Location' => $url]);
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        if ($this->headers === [] && $this->isJson($this->body)) {
            header('Content-Type: application/json; charset=utf-8');
        }

        echo $this->body;
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }

    protected function isJson(string $data): bool
    {
        if ($data === '') {
            return false;
        }

        json_decode($data);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
