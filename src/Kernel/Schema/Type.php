<?php

declare(strict_types=1);

namespace Snidget\Kernel\Schema;

use DateTimeInterface;
use JsonSerializable;
use ReflectionNamedType;
use Snidget\Kernel\Reflection;
use TypeError;

abstract class Type implements JsonSerializable
{
    protected string $dateFormat = 'd.m.Y H:i:s';

    /**
     * @param array<string, mixed> $array
     */
    public function __construct(array $array = [])
    {
        $this->fromArray($array);
    }

    /**
     * @param array<string, mixed> $array
     */
    public function fromArray(array $array = []): self
    {
        $reflection = new Reflection($this);
        foreach ($this->getDefaultPublicFields() as $key => $default) {
            $value = $this->getValue($key, $array[$key] ?? $default);

            try {
                $reflection->getProperty($key)->setValue($this, $value);
            } catch (TypeError $e) {
                $message = sprintf(
                    "Не удалось установить в поле %s::%s значение %s",
                    static::class,
                    $key,
                    is_scalar($value) ? (string)$value : get_debug_type($value)
                );
                throw new TypeError($message, $e->getCode(), $e);
            }
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $array = [];
        foreach ($this->getDefaultPublicFields() as $key => $el) {
            if ($el instanceof self || $el instanceof Collection) {
                $array[$key] = $el->toArray();
            } elseif ($el instanceof DateTimeInterface) {
                $array[$key] = $el->format($this->dateFormat);
            } else {
                $array[$key] = $el;
            }
        }

        return $array;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array
    {
        return $this->toArray();
    }

    protected function getValue(string $key, mixed $value): mixed
    {
        $prop = new Reflection($this)->getProperty($key);
        $type = $prop->getType();
        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return $value;
        }

        $className = $type->getName();
        $doc = $prop->getDocComment();
        if ($doc !== false && is_array($value) && preg_match('#\$\S+ (\S+)\[]#', $doc, $match) === 1) {
            $itemClass = $match[1];
            return new Collection($value)->map(fn($x): object => new $itemClass($x));
        }

        if (!in_array($value, [null, '', []], true) && !is_object($value)) {
            return new $className($value);
        }

        return $value;
    }

    /**
     * @return \Generator<string, mixed>
     */
    protected function getDefaultPublicFields(): \Generator
    {
        foreach (new Reflection($this)->getPublicProperties() as $property) {
            $value = $property->isInitialized($this) ? $property->getValue($this) : null;
            $type = $property->getType();

            if ($value === null && $type instanceof ReflectionNamedType && $type->getName() === 'array') {
                $value = [];
            }

            yield $property->getName() => $value;
        }
    }
}
