<?php

namespace App\DTO\Database;

use Snidget\Attribute\Column;
use Snidget\Enum\SQLiteType;
use Snidget\Typing\Type;

class People extends Type
{
    #[Column(name: 'id', type: SQLiteType::INTEGER, autoincrement: true)]
    public ?int $id;
    #[Column(name: 'name')]
    public ?string $name = 'test';
    #[Column(name: 'age', isNull: true, default: 18)]
    public int $age = 18;
}
