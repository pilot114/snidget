<?php

namespace Snidget;

use LogicException;
use Snidget\Exception\SnidgetException;

class Request
{
    public string $uri;
    public mixed $data;

    public function __construct()
    {
        $uri = $_SERVER['QUERY_STRING']
            ?? $_SERVER['REQUEST_URI']
            ?? throw new SnidgetException('Нет строки запроса в $_SERVER');

        $this->uri = trim($uri, '/');
        $this->data = json_decode(file_get_contents('php://input'), true);
    }
}