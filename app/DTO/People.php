<?php

namespace App\DTO;

use Wshell\Snidget\Attribute\Column;
use Wshell\Snidget\Typing\Type;

class People extends Type
{
    #[Column(name: 'id', type: 'INTEGER', autoincrement: true)]
    public ?int $id;
    #[Column(name: 'name')]
    public ?string $name = 'test';
    #[Column(name: 'age', isNull: true, default: 18)]
    public int $age = 18;
}
