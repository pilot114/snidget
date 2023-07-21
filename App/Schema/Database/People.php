<?php

namespace App\Schema\Database;

use Snidget\Database\Column;
use Snidget\Database\SQLiteType;
use Snidget\Kernel\Typing\Type;

class People extends Type
{
    #[Column(name: 'id', type: SQLiteType::INTEGER, autoincrement: true)]
    public ?int $id;
    #[Column(name: 'name')]
    public ?string $name = 'test';
    #[Column(name: 'age', isNull: true, default: 18)]
    public int $age = 18;
}
