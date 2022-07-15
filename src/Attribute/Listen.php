<?php

namespace Snidget\Attribute;

use Attribute;
use Snidget\Enum\SystemEvent;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Listen
{
    public function __construct(
        protected SystemEvent $event,
    ){}

    public function getEvent(): SystemEvent
    {
        return $this->event;
    }
}