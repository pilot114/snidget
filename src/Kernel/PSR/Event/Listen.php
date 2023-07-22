<?php

namespace Snidget\Kernel\PSR\Event;

use Attribute;
use UnitEnum;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Listen
{
    public function __construct(
        protected UnitEnum $event,
    ) {
    }

    public function getEvent(): UnitEnum
    {
        return $this->event;
    }
}
