<?php

namespace App\Controller;

use Wshell\Snidget\Attribute\Route;
use Wshell\Snidget\Container;
use Wshell\Snidget\Router;
use Wshell\Snidget\Table;

class Example
{
    #[Route('')]
    public function index(Router $router): string
    {
        return json_encode($router->routes());
    }

    #[Route('post')]
    public function list(Container $container): string
    {
        $table = $container->get(Table::class, ['name' => 'test']);
        $data = $table->create();
        dump($data);
        die();

        return 'Post::list';
    }

    #[Route('post/(?<id>\d+)')]
    public function get(int $id): string
    {
        return 'Post::get #' . $id;
    }

    #[Route('.*')]
    public function notFound(): string
    {
        http_response_code(404);
        return '404 Not Found';
    }
}