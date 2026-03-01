<?php

namespace Snidget\Tests\Fixtures;

use Snidget\Kernel\Assert;
use Snidget\Kernel\Schema\Type;

class AssertableType extends Type
{
    #[Assert(min: 1, max: 100)]
    public int $age = 0;

    #[Assert(minLength: 3, maxLength: 20)]
    public string $name = '';

    #[Assert(pattern: '/^[a-zA-Z0-9_]+$/')]
    public string $username = '';

    #[Assert(notBlank: true)]
    public string $email = '';
}
