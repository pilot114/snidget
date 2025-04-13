<?php

namespace Snidget\Kernel;

use Attribute;
use Generator;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;

class Reflection
{
    const ATTR_PROPERTY = Attribute::TARGET_PROPERTY;
    const ATTR_METHOD = Attribute::TARGET_METHOD;
    const ATTR_CLASS = Attribute::TARGET_CLASS;

    protected ReflectionClass $class;

    /**
     * @throws ReflectionException
     */
    public function __construct(string|object $class)
    {
        $this->class = new ReflectionClass(is_object($class) ? $class::class : $class);
    }

    public function isAbstract(): bool
    {
        return $this->class->isAbstract();
    }

    public function getMethod(string $name): ReflectionMethod
    {
        return $this->class->getMethod($name);
    }

    public function getMethods(): array
    {
        return $this->class->getMethods();
    }

    public function getPublicProperties(): array
    {
        return $this->class->getProperties(ReflectionProperty::IS_PUBLIC);
    }

    /**
     * @throws ReflectionException
     */
    public function getProperty(string $propName): ReflectionProperty
    {
        return $this->class->getProperty($propName);
    }

    /**
     * @template T
     * @param class-string<T> $attrName
     * @return Generator<string, T>
     */
    public function getAttributes(int $type, string $attrName): Generator
    {
        $tmp = match ($type) {
            self::ATTR_PROPERTY => $this->getPublicProperties(),
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

    public function getPropAttributes(string $propName, string $attrName): array
    {
        return $this->class->getProperty($propName)->getAttributes($attrName);
    }

    public function getParams(string $methodName): \Generator
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
