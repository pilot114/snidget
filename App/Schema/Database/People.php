<?php

namespace App\Schema\Database;

use Snidget\Database\SQLite\Column;
use Snidget\Database\SQLite\Type;
use Snidget\Kernel\Schema\Type as BaseType;

class People extends BaseType
{
    #[Column(name: 'id', type: Type::INTEGER, autoincrement: true)]
    public ?int $id;
    #[Column(name: 'name')]
    public ?string $name = 'test';
    #[Column(name: 'age', isNull: true, default: 18)]
    public int $age = 18;
}
