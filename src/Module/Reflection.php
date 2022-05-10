<?php

namespace Snidget\Module;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use ReflectionAttribute;
use Attribute;

class Reflection
{
    const ATTR_PROPERTY = Attribute::TARGET_PROPERTY;
    const ATTR_METHOD = Attribute::TARGET_METHOD;

    protected ReflectionClass $class;

    public function __construct(string|object $class)
    {
        $this->class = new ReflectionClass(is_object($class) ? $class::class : $class);
    }

    public function getMethod(string $methodName): ReflectionMethod
    {
        return $this->class->getMethod($methodName);
    }

    /**
     * @return ReflectionMethod[]
     */
    public function getMethods(): array
    {
        return $this->class->getMethods();
    }

    /**
     * @return ReflectionProperty[]
     */
    public function getProperties(): array
    {
        return $this->class->getProperties();
    }

    public function getProperty(string $propName): ReflectionProperty
    {
        return $this->class->getProperty($propName);
    }

    public function getAttributes(int $type, string $attrName): iterable
    {
        $tmp = match ($type) {
            self::ATTR_PROPERTY => $this->getProperties(),
            self::ATTR_METHOD => $this->getMethods(),
        };
        foreach ($tmp as $item) {
            foreach ($item->getAttributes($attrName) as $attribute) {
                yield $item->getName() => $attribute;
            }
        }
    }

    public function getParams(string $methodName): iterable
    {
        $params = match ($methodName) {
            '__construct' => $this->class->getConstructor()?->getParameters() ?? [],
            default => $this->getMethod($methodName)?->getParameters() ?? [],
        };
        foreach ($params as $param) {
            yield $param;
        }
    }
}