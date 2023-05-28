<?php

namespace Snidget\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Arg
{
    public function __construct(
        protected string $description,
        protected bool $isOption = true,
        protected ?string $short = null,
    ) {
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function isOption(): bool
    {
        return $this->isOption;
    }

    public function getShort(): ?string
    {
        return $this->short;
    }
}
