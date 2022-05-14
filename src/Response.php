<?php

namespace Snidget;

class Response
{
    public function __construct(
        protected string $data
    ){}

    public function send(): never
    {
        if ($this->isJson($this->data)) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo $this->data;
        die();
    }

    protected function isJson(string $data): bool
    {
        json_decode($data);
        return json_last_error() === JSON_ERROR_NONE;
    }
}