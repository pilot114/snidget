<?php

namespace Snidget;

use LogicException;

class Request
{
    protected string $uri;

    public function __construct()
    {
        $uri = $_SERVER['QUERY_STRING']
            ?? $_SERVER['REQUEST_URI']
            ?? throw new LogicException('Нет строки запроса в режиме fpm');

        $this->uri = trim($uri, '/');
    }

    public function getUri(): string
    {
        return $this->uri;
    }
}