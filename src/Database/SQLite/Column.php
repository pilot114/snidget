<?php

namespace Snidget\Database\SQLite;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public ?string $ref = null;

    public function __construct(
        public string $name,
        public Type   $type = Type::TEXT,
        public bool   $isNull = false,
        public bool   $autoincrement = false,
        public bool   $isUnsigned = false,
        public mixed  $default = null,
        public ?int   $length = null
    ) {
    }

    public function getDefinition(): string
    {
        return sprintf(
            '%s %s%s%s%s%s',
            $this->name,
            ($this->isUnsigned && !$this->autoincrement) ? 'UNSIGNED ' : '',
            $this->type->name,
            $this->autoincrement ? ' PRIMARY KEY AUTOINCREMENT' : '',
            $this->isNull ? '' : ' NOT NULL',
            $this->default ? ' DEFAULT ' . $this->default : ''
        );
    }
}
