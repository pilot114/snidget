<?php

declare(strict_types=1);

namespace Snidget\CLI;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Arg
{
    public function __construct(
        public readonly string $description,
        public readonly bool $isOption = true,
        public readonly ?string $short = null,
    ) {
    }
}
