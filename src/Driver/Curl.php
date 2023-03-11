<?php

namespace Snidget\Driver;

class Curl
{
    protected array $options;

    public function __construct()
    {
        $this->options = [
            'http' => [
                'method' => 'GET',
                'header' => 'Accept-language: en\r\n'
                           . 'Cookie: foo=bar\r\n'
            ]
        ];
    }

    public function get(string $url): string
    {
        $context = stream_context_create($this->options);
        return file_get_contents($url, context: $context) ?: '';
    }
}
