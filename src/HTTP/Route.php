<?php

namespace Snidget\HTTP;

use Attribute;
use Snidget\Kernel\SnidgetException;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Route
{
    public function __construct(
        protected string $regex = '',
        protected string $prefix = '',
    ) {
        if ($this->prefix && $this->regex) {
            throw new SnidgetException('Для аттрибута Route нельзя задать вместе prefix и regex');
        }
    }

    public function getRegex(): string
    {
        return $this->regex;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }
}
