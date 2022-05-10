<?php

namespace Snidget\Attribute;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        protected string $regex,
    ){}

    public function getRegex(): string
    {
        return $this->regex;
    }
}