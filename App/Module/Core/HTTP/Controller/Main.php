<?php

namespace App\Module\Core\HTTP\Controller;

use Snidget\HTTP\Response;
use Snidget\HTTP\Route;

class Main
{
    #[Route(regex: '')]
    public function index(): string
    {
        return 'main';
    }

    #[Route(regex: '.*')]
    public function notFound(): Response
    {
        return new Response('404 Not Found', 404);
    }
}
