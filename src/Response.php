<?php

namespace Snidget;

class Response
{
    public function __construct(
        protected string $data
    ){}

    public function send(): void
    {
        if ($this->isJson($this->data)) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo $this->data;
    }

    protected function isJson(string $data): bool
    {
        json_decode($data);
        return json_last_error() === JSON_ERROR_NONE;
    }
}