<?php

namespace Snidget\Tests\Fixtures;

use Snidget\Database\SQLite\Column;
use Snidget\Database\SQLite\Type;
use Snidget\Kernel\Schema\Type as SchemaType;

class TestSchema extends SchemaType
{
    #[Column(name: 'id', type: Type::INTEGER, autoincrement: true)]
    public ?int $id = null;

    #[Column(name: 'name', type: Type::TEXT)]
    public ?string $name = null;

    #[Column(name: 'age', type: Type::INTEGER, isNull: true, default: 18)]
    public int $age = 18;
}
