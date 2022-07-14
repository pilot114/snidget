<?php

namespace Snidget\Attribute;

use Attribute;
use Snidget\Exception\SnidgetException;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class Listen
{
    public function __construct(
        protected string $eventName = '',
    ){}
}