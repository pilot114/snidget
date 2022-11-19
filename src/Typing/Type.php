<?php

namespace Snidget\Typing;

use Snidget\Module\Reflection;
use JsonSerializable;
use DateTimeInterface;
use TypeError;
use Error;

abstract class Type implements JsonSerializable
{
    protected string $dateFormat = 'd.m.Y H:i:s';
    protected array $useFields = [];

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
                throw new TypeError($message);
            }
        }
        return $this;
    }

    public function toArray(): array
    {
        $array = [];
        foreach ($this->getUsedPublic() as $key => $el) {
            if ($el instanceof Type || $el instanceof Collection) {
                $array[$key] = $el->toArray();
            }
            if ($el instanceof DateTimeInterface) {
                $array[$key] = $el->format($this->dateFormat);
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
        $prop = (new Reflection($this))->getProperty($key);
        /** @var \ReflectionNamedType|null $type */
        $type = $prop->getType();

        if ($type && !$type->isBuiltin()) {
            $className = $type->getName();

            if ($doc = $prop->getDocComment()) {
                preg_match('#\$\S+ (\S+)\[]#', $doc, $match);
                $itemClass = $match[1] ?? null;
                if ($itemClass) {
                    return (new Collection($value))->map(fn($x) => new $itemClass($x));
                }
            }
            if ($value) {
                return is_object($value) ? $value : new $className($value);
            }
        }
        return $value;
    }

    protected function getDefaultPublicFields(): \Generator
    {
        foreach ((new Reflection($this))->getPublicProperties() as $property) {
            if (!$property->isPublic()) {
                continue;
            }
            $value = $this->{$property->getName()} ?? null;
            $type = $property->getType();

            if ($type && !$value && $type->getName() === 'array') {
                $value = [];
            }
            yield $property->getName() => $value;
        }
    }

    protected function getUsedPublic(): \Generator
    {
        foreach ((new Reflection($this))->getPublicProperties() as $property) {
            if (!$property->isPublic() || !in_array($property->getName(), array_flip($this->useFields))) {
                continue;
            }
            try {
                yield $property->getName() => $this->{$property->getName()};
            } catch (Error $e) {
                $message = sprintf("Не удалось прочитать поле %s::%s", static::class, $property->getName());
                throw new TypeError($message);
            }
        }
    }
}
