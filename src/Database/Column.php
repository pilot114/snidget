<?php

namespace Snidget\Database;

use Attribute;
use Snidget\Kernel\Typing\Type;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public string $name,
        public SQLiteType $type = SQLiteType::TEXT,
        public bool $isNull = false,
        public bool $autoincrement = false,
        public bool $isUnsigned = false,
        public mixed $default = null,
        public ?int $length = null
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

    public function getInsertDefinition(Type $data): string
    {
        $value = $data->{$this->name};
        if (is_string($value) && $this->type === SQLiteType::TEXT) {
            return "'$value'";
        }
        return (string)$value;
    }
}
