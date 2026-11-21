<?php

namespace App\Module\Core\HTTP\Controller;

use Snidget\HTTP\Route;

class Main
{
    #[Route(regex: '')]
    public function index(): string
    {
        return 'main';
    }
}
