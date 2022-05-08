<?php

namespace Wshell\Snidget\Attribute;
use Attribute;
use Wshell\Snidget\Typing\Type;

//enum SQLiteType
//{
//    case NULL;
//    case INTEGER;
//    case REAL;
//    case TEXT;
//    case BLOB;
//}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public string $name,
        public string $type = 'TEXT',
        public bool $isNull = false,
        public bool $autoincrement = false,
        public mixed $default = null,
        public ?int $length = null
    ){}

    public function getDefinition(): string
    {
        return sprintf(
            '%s %s%s%s%s',
            $this->name,
            $this->type,
            $this->autoincrement ? ' PRIMARY KEY AUTOINCREMENT' : '',
            $this->isNull ? '' : ' NOT NULL',
            $this->default ? ' DEFAULT ' . $this->default : ''
        );
    }

    public function getInsertDefinition(Type $data): string
    {
        $value = $data->{$this->name};
        if (is_string($value) && $this->type === 'TEXT') {
            return "'$value'";
        }
        return (string)$value;
    }
}