<?php

namespace Wshell\Snidget\Typing;

use JsonSerializable;
use DateTimeInterface;
use ReflectionProperty;
use ReflectionClass;
use TypeError;
use Error;

abstract class Type implements JsonSerializable
{
    /**
     * Формат даты на выходе toArray
     */
    protected string $dateFormat = 'd.m.Y H:i:s';

    /**
     * Для перечисления полей, которые нужны на выходе toArray
     */
    protected array $useFields = [];

    public function __construct(array $array = [])
    {
        $this->fromArray($array);
    }

    public function setDateFormat($format)
    {
        $this->dateFormat = $format;
    }

    public function setUseFields($useFields)
    {
        $this->useFields = $useFields;
    }

    /**
     * Все поля всегда есть и доступны для чтения
     *
     * Поведение, если значение не передано:
     * - если есть значение по умолчанию - ставится оно
     * - если оно может быть null - ставится null
     * - если Type/Collection/DateTime - пытаемся инстанцировать
     */
    public function fromArray($array = []): self
    {
        $fields = $this->getDefaultPublicFields();

        foreach ($fields as $key => $default) {
            $value = $array[$key] ?? $default;

            $prop = new ReflectionProperty($this, $key);
            $type = $prop->getType();

            if ($type && !$type->isBuiltin()) {
                $className = $type->getName();

                if ($doc = $prop->getDocComment()) {
                    preg_match('#\$\S+ (\S+)\[]#', $doc, $match);
                    $itemClass = $match[1] ?? null;
                    if ($itemClass) {
                        $value =  (new Collection($value))->map(fn($x) => new $itemClass($x));
                    }
                }
                if ($value) {
                    $value = is_object($value) ? $value : new $className($value);
                }
            }

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
        $rc = new ReflectionClass($this);

        $fields = [];
        foreach ($rc->getProperties() as $property) {
            if (!$property->isPublic()) {
                continue;
            }
            try {
                $fields[$property->getName()] = $this->{$property->getName()};
            } catch (Error $e) {
                $message = sprintf("Не удалось прочитать поле %s::%s", static::class, $property->getName());
                throw new TypeError($message);
            }
        }

        if ($this->useFields) {
            $fields = array_intersect_key($fields, array_flip($this->useFields));
        }
        foreach ($fields as $i => $el) {
            if ($el instanceof Type || $el instanceof Collection) {
                $fields[$i] = $el->toArray();
            }

            if ($el instanceof DateTimeInterface) {
                $fields[$i] = $el->format($this->dateFormat);
            }
        }

        return $fields;
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __serialize(): array
    {
        return $this->toArray();
    }

    /**
     * Получаем поля со значениями по умолчанию
     */
    protected function getDefaultPublicFields(): array
    {
        $fields = [];
        $rc = new ReflectionClass($this);

        foreach ($rc->getProperties() as $property) {
            if (!$property->isPublic()) {
                continue;
            }
            $value = $this->{$property->getName()} ?? null;
            $type = $property->getType();

            if ($type && !$value) {
                if ($type->getName() === 'array') {
                    $value = [];
                }
            }
            $fields[$property->getName()] = $value;
        }

        return $fields;
    }
}
