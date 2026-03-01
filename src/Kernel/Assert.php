<?php

namespace Snidget\Kernel;

use Attribute;
use Snidget\Kernel\Schema\Type;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
class Assert
{
    public function __construct(
        public ?int $min = null,
        public ?int $max = null,
        public ?int $minLength = null,
        public ?int $maxLength = null,
        public ?string $pattern = null,
        public bool $notBlank = false,
    ) {
    }

    public function check(mixed $value): array
    {
        $errors = [];
        if ($this->notBlank && ($value === null || $value === '')) {
            $errors[] = 'Значение не может быть пустым';
        }
        if (is_int($this->min) && $value < $this->min) {
            $errors[] = 'Значение меньше ' . $this->min;
        }
        if (is_int($this->max) && $value > $this->max) {
            $errors[] = 'Значение больше ' . $this->max;
        }
        if (is_int($this->minLength) && is_string($value) && mb_strlen($value) < $this->minLength) {
            $errors[] = "Значение короче {$this->minLength} символов";
        }
        if (is_int($this->maxLength) && is_string($value) && mb_strlen($value) > $this->maxLength) {
            $errors[] = "Значение длиннее {$this->maxLength} символов";
        }
        if ($this->pattern !== null && is_string($value) && !preg_match($this->pattern, $value)) {
            $errors[] = "Значение не соответствует шаблону {$this->pattern}";
        }
        return $errors;
    }

    /**
     * @return array<string, string[]>
     */
    public static function validateType(Type $type): array
    {
        $errors = [];
        foreach (AttributeLoader::getAssertions($type::class) as $fqn => $assert) {
            $propName = explode('::', $fqn)[1];
            $value = $type->{$propName};
            $fieldErrors = $assert->check($value);
            if ($fieldErrors !== []) {
                $errors[$propName] = array_merge($errors[$propName] ?? [], $fieldErrors);
            }
        }
        return $errors;
    }
}
