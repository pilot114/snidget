<?php

namespace App\HTTP\Controller;

use App\Schema\Database\People;
use Snidget\Attribute\Route;
use Snidget\SQL\Table;
use Snidget\Psr\Container;

#[Route(prefix: 'api/v1')]
class Api
{
    #[Route(regex: 'post')]
    public function list(Container $container): string
    {
        $table = $container->get(Table::class, [
            'name' => 'test',
            'type' => $container->get(People::class)
        ]);

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
    public function get(int $id, Container $container): string
    {
        $table = $container->get(Table::class, [
            'name' => 'test',
            'type' => $container->get(People::class)
        ]);

        $data = $table->read($id);

        return json_encode($data);
    }
}