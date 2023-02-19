<?php

namespace App\Module\Core\Schema\Command;

use Snidget\Attribute\Arg;
use Snidget\Typing\Type;

class AutoCompleteInput extends Type
{
    #[Arg('The index of the "input" array that the cursor is in (e.g. COMP_CWORD)', shortcut: 'c')]
    public ?int $current = 0;
    #[Arg('An array of input tokens (e.g. COMP_WORDS or argv)', shortcut: 'i')]
    public array $input = [];
}