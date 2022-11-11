<?php

namespace Snidget\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Assert
{
    public function __construct(
        public ?int $min = null,
        public ?int $max = null,
        public ?int $minLength = null,
        public ?int $maxLength = null,
    ) {
    }

    public function check(mixed $value): array
    {
        $errors = [];
        if (isset($this->min) && $value < $this->min) {
            $errors[] = 'Значение меньше ' . $this->min;
        }
        if (isset($this->max) && $value > $this->max) {
            $errors[] = 'Значение больше ' . $this->max;
        }
        if (isset($this->minLength) && mb_strlen($value) < $this->minLength) {
            $errors[] = "Значение короче {$this->minLength} символов";
        }
        if (isset($this->maxLength) && mb_strlen($value) > $this->maxLength) {
            $errors[] = "Значение длинее {$this->maxLength} символов";
        }
        return $errors;
    }
}
