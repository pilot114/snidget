<?php

namespace App\Module\Core\Schema\Command;

use Snidget\CLI\Arg;
use Snidget\Kernel\Schema\Type;

class AutoCompleteInput extends Type
{
    #[Arg('The index of the "input" array that the cursor is in (e.g. COMP_CWORD)', short: 'c')]
    public ?int $current = 0;
    #[Arg('An array of input tokens (e.g. COMP_WORDS or argv)', short: 'i')]
    public array $input = [];
}
