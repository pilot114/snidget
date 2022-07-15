<?php

namespace App\HTTP\Controller;

use Snidget\Attribute\Route;
use Snidget\Attribute\Listen;
use Snidget\Enum\SystemEvent;

class Main
{
    #[Route(regex: '')]
    public function index(): string
    {
        return 'main';
    }

    #[Route(regex: '.*')]
    public function notFound(): string
    {
        http_response_code(404);
        return '404 Not Found';
    }

    #[Listen(SystemEvent::START)]
    public function listener($eventData): void
    {
        dump($eventData);
        dump('listener run!');
    }
}