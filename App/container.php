<?php

use App\Schema\Database\People;
use Snidget\Database\SQLite\Table;
use Snidget\Kernel\PSR\Container;

return [
    // TODO: 2 optional arg is call context class
    // example
    Table::class => function (Container $c) {
        return $c->get(Table::class, [
            'name' => 'test',
            'type' => $c->get(People::class)
        ]);
    },
];
