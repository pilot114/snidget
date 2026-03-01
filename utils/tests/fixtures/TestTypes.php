<?php

namespace Snidget\Tests\Fixtures;

use Snidget\Kernel\Schema\Type;
use Snidget\Kernel\Schema\Collection;

class SimpleType extends Type
{
    public string $name = '';
    public int $age = 0;
    public float $score = 0.0;
    public bool $active = false;
}

class AddressType extends Type
{
    public string $city = '';
    public string $street = '';
}

class NestedType extends Type
{
    public string $name = '';
    public ?AddressType $address = null;
}

class CollectionType extends Type
{
    public string $title = '';
    /** @var SimpleType[] */
    public ?Collection $items = null;
}
