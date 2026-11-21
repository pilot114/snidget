<?php

namespace Dev\Tests\Fixtures\Command;

use Snidget\CLI\Arg;
use Snidget\CLI\Command;
use Snidget\Kernel\Schema\Type;

class FloatInput extends Type
{
    #[Arg('ratio')]
    public ?float $ratio = null;

    public string $notArg = '';

    /** @var list<string> */
    #[Arg('rest', isOption: false)]
    public array $rest = [];
}

class StrictInput extends Type
{
    #[Arg('not nullable')]
    public string $name = '';
}

class Fixture
{
    #[Command('float option')]
    public function floats(FloatInput $data): void
    {
    }

    #[Command]
    public function strict(StrictInput $data): void
    {
    }
}
