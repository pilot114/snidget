<?php

namespace App\HTTP\Controller;

use App\Schema\Database\People;
use Snidget\Database\SQLite\Table;
use Snidget\HTTP\Route;
use Snidget\Kernel\PSR\Container;
use Snidget\Kernel\SnidgetException;

#[Route(prefix: 'api/v1')]
class Api
{
    /** @return array{total: int, items: list<array<string, mixed>>} */
    #[Route(regex: 'post')]
    public function list(Container $container): array
    {
        $table = $this->table($container);
        return [
            'total' => $table->count(),
            'items' => $table->like('TEST', 'name'),
        ];
    }

    /** @return array<string, mixed> */
    #[Route(regex: 'post/(?<id>\d+)')]
    public function get(int $id, Container $container): array
    {
        $post = $this->table($container)->read($id);
        return $post !== [] ? $post : throw new SnidgetException("Пост $id не найден", 404);
    }

    // the database is in memory by default, so it is empty on each request
    protected function table(Container $container): Table
    {
        $table = $container->get(Table::class, ['name' => 'test', 'type' => $container->get(People::class)]);
        if (!$table->exist()) {
            $table->create();
            $table->insert($table->getType());
            $table->insert($table->getType());
            $table->insert($table->getType());
        }

        return $table;
    }
}
