<?php

namespace Snidget\Kernel;

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
        if (is_int($this->min) && $value < $this->min) {
            $errors[] = 'Значение меньше ' . $this->min;
        }
        if (is_int($this->max) && $value > $this->max) {
            $errors[] = 'Значение больше ' . $this->max;
        }
        if (is_int($this->minLength) && mb_strlen($value) < $this->minLength) {
            $errors[] = "Значение короче {$this->minLength} символов";
        }
        if (is_int($this->maxLength) && mb_strlen($value) > $this->maxLength) {
            $errors[] = "Значение длиннее {$this->maxLength} символов";
        }
        return $errors;
    }
}
