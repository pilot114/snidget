<?php

namespace Snidget\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Command
{
    public function __construct(
        protected ?string $description = null,
    ) {
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
