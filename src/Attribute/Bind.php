<?php

namespace Snidget\Attribute;
use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Bind
{
    public function __construct(
        protected ?string $class = null,
        protected ?string $method = null,
        protected int $priority = 0,
    ){}

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

}