<?php

namespace Snidget;

use LogicException;

class Request
{
    public string $uri;
    public mixed $data;

    public function __construct()
    {
        $uri = $_SERVER['QUERY_STRING']
            ?? $_SERVER['REQUEST_URI']
            ?? throw new LogicException('Нет строки запроса в режиме fpm');

        $this->uri = trim($uri, '/');
        $this->data = json_decode(file_get_contents('php://input'), true);
    }
}