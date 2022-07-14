<?php

namespace Snidget\Module;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use Attribute;
use Generator;

class Reflection
{
    const ATTR_PROPERTY = Attribute::TARGET_PROPERTY;
    const ATTR_METHOD = Attribute::TARGET_METHOD;
    const ATTR_CLASS = Attribute::TARGET_CLASS;

    protected ReflectionClass $class;

    public function __construct(string|object $class)
    {
        $this->class = new ReflectionClass(is_object($class) ? $class::class : $class);
    }

    public function getMethods(): array
    {
        return $this->class->getMethods();
    }

    public function getProperties(): array
    {
        return $this->class->getProperties(ReflectionProperty::IS_PUBLIC);
    }

    public function getProperty(string $propName): ReflectionProperty
    {
        return $this->class->getProperty($propName);
    }

    /**
     * @template T
     * @param class-string<T> $attrName
     * @return Generator?<string, T>
     */
    public function getAttributes(int $type, string $attrName): iterable
    {
        $tmp = match ($type) {
            self::ATTR_PROPERTY => $this->getProperties(),
            self::ATTR_METHOD   => $this->getMethods(),
            self::ATTR_CLASS    => [$this->class],
            default             => throw new \UnhandledMatchError()
        };
        foreach ($tmp as $item) {
            foreach ($item->getAttributes($attrName) as $attribute) {
                /** @var ReflectionClass | ReflectionMethod | ReflectionProperty $item */
                $fqn = isset($item->class) ? ($item->class . '::' . $item->getName()) : $item->getName();
                yield $fqn => $attribute->newInstance();
            }
        }
    }

    public function getParams(string $methodName): iterable
    {
        $params = match ($methodName) {
            '__construct' => $this->class->getConstructor()?->getParameters() ?? [],
            default       => $this->class->getMethod($methodName)->getParameters(),
        };
        foreach ($params as $param) {
            yield $param;
        }
    }
}