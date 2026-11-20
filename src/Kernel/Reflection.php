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
    public const ATTR_PROPERTY = Attribute::TARGET_PROPERTY;

    public const ATTR_METHOD = Attribute::TARGET_METHOD;

    public const ATTR_CLASS = Attribute::TARGET_CLASS;

    /** @var ReflectionClass<object> */
    protected ReflectionClass $class;

    /**
     * @throws ReflectionException
     */
    public function __construct(string|object $class)
    {
        if (is_string($class) && !class_exists($class) && !interface_exists($class) && !trait_exists($class)) {
            throw new ReflectionException("Class \"$class\" does not exist");
        }

        $this->class = new ReflectionClass($class);
    }

    public function isAbstract(): bool
    {
        return $this->class->isAbstract();
    }

    public function getMethod(string $name): ReflectionMethod
    {
        return $this->class->getMethod($name);
    }

    /** @return list<ReflectionMethod> */
    public function getMethods(): array
    {
        return $this->class->getMethods();
    }

    /** @return list<ReflectionProperty> */
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
     * Атрибуты по ключу Класс::член (для ATTR_CLASS — имя класса)
     *
     * @template T of object
     * @param class-string<T> $attrName
     * @return Generator<string, T>
     */
    public function getAttributes(int $type, string $attrName): Generator
    {
        $items = match ($type) {
            self::ATTR_PROPERTY => $this->getPublicProperties(),
            self::ATTR_METHOD   => $this->getMethods(),
            self::ATTR_CLASS    => [$this->class],
            default             => throw new \UnhandledMatchError()
        };
        foreach ($items as $item) {
            $fqn = $item instanceof ReflectionClass ? $item->getName() : $item->class . '::' . $item->getName();
            foreach ($item->getAttributes($attrName) as $attribute) {
                yield $fqn => $attribute->newInstance();
            }
        }
    }

    /**
     * @template T of object
     * @param class-string<T> $attrName
     * @return list<\ReflectionAttribute<T>>
     */
    public function getPropAttributes(string $propName, string $attrName): array
    {
        return $this->class->getProperty($propName)->getAttributes($attrName);
    }

    /** @return Generator<int, \ReflectionParameter> */
    public function getParams(string $methodName): Generator
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
