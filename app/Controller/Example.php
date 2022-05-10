<?php

namespace App\Controller;

use App\DTO\Database\People;
use Snidget\Attribute\Route;
use Snidget\Container;
use Snidget\Router;
use Snidget\Table;

class Example
{
    #[Route(regex: '')]
    public function index(Router $router): string
    {
        foreach ($router->routes() as $regex => $route) {
            $link = sprintf('<a href="%s">%s (%s)</a>', $regex, $route, $regex);
            dump($link);
        }
        return '';
    }

    #[Route(regex: 'post')]
    public function list(Container $container): string
    {
        $dto = $container->get(People::class);
        $table = $container->get(Table::class, ['name' => 'test', 'type' => $dto]);

        if (!$table->exist()) {
            $table->create();
            $table->insert($table->getType());
            $table->insert($table->getType());
            $table->insert($table->getType());
//            dump($container->get(\Snidget\Module\PDO::class)->getLog());
        }

        $data = json_encode([
            'total' => $table->count(),
            'items' => $table->like('TEST', 'name'),
        ]);

        return $data;
    }

    #[Route(regex: 'post/(?<id>\d+)')]
    public function get(int $id): string
    {
        return 'Post::get #' . $id;
    }

    #[Route(regex: '.*')]
    public function notFound(): string
    {
        http_response_code(404);
        return '404 Not Found';
    }
}