<?php

namespace App\HTTP\Controller;

use App\DTO\Database\People;
use Snidget\Attribute\Bind;
use Snidget\Attribute\Route;
use Snidget\Container;
use Snidget\Table;

#[Route(prefix: 'api/v1')]
class Api
{
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

        return json_encode([
            'total' => $table->count(),
            'items' => $table->like('TEST', 'name'),
        ]);
    }

    #[Route(regex: 'post/(?<id>\d+)')]
    public function get(int $id): string
    {
        return 'Post::get #' . $id;
    }
}