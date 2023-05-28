<?php

namespace App\Module\Core\Schema\Command;

use Snidget\Attribute\Arg;
use Snidget\Typing\Type;

class TestInput extends Type
{
    #[Arg('alpha option', short: 'a')]
    public array $alpha = [];
    #[Arg('beta option', short: 'b')]
    public ?string $beta = null;
    #[Arg('gamma option')]
    public ?string $gamma = null;
    #[Arg('delta option', short: 'd')]
    public bool $delta = false;
    #[Arg('epsilon option', short: 'e')]
    public bool $epsilon = false;

    #[Arg('first arg', isOption: false)]
    public string $first;
    #[Arg('second arg', isOption: false)]
    public ?string $second = null;
    // TODO: array может, быть и в начале и в конце
    #[Arg('third arg', isOption: false)]
    public array $third = [];
}