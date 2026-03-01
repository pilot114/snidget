<?php

declare(strict_types=1);

namespace Snidget\Kernel\Schema;

use DateTimeInterface;
use JsonSerializable;
use Snidget\Kernel\Reflection;
use TypeError;

abstract class Type implements JsonSerializable
{
    protected string $dateFormat = 'd.m.Y H:i:s';
    public function __construct(array $array = [])
    {
        $this->fromArray($array);
    }

    public function fromArray(array $array = []): self
    {
        foreach ($this->getDefaultPublicFields() as $key => $default) {
            $value = $this->getValue($key, $array[$key] ?? $default);

            try {
                $this->$key = $value;
            } catch (TypeError $e) {
                $message = sprintf("Не удалось установить в поле %s::%s значение %s", static::class, $key, $value ?? 'null');
                throw new TypeError($message, $e->getCode(), $e);
            }
        }
        return $this;
    }

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

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }

    protected function getValue(string $key, mixed $value): mixed
    {
        $prop = new Reflection($this)->getProperty($key);
        /** @var \ReflectionNamedType|null $type */
        $type = $prop->getType();

        if ($type && !$type->isBuiltin()) {
            $className = $type->getName();

            if ($doc = $prop->getDocComment()) {
                preg_match('#\$\S+ (\S+)\[]#', $doc, $match);
                $itemClass = $match[1] ?? null;
                if ($itemClass) {
                    return new Collection($value)->map(fn($x): object => new $itemClass($x));
                }
            }
            if ($value && !is_object($value)) {
                return new $className($value);
            }
        }
        return $value;
    }

    protected function getDefaultPublicFields(): \Generator
    {
        foreach (new Reflection($this)->getPublicProperties() as $property) {
            $value = $this->{$property->getName()} ?? null;
            $type = $property->getType();

            if ($type && !$value && $type->getName() === 'array') {
                $value = [];
            }
            yield $property->getName() => $value;
        }
    }
}
