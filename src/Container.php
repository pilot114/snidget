<?php

namespace Snidget;

use Snidget\Exception\SnidgetException;
use Snidget\Module\Reflection;

class Container
{
    protected static array $pool = [];

    public function call(object|string $instance, string $methodName, array $params = []): mixed
    {
        $realParams = $this->getParams($instance, $methodName, $params);

        if ((new Reflection($instance))->getMethod($methodName)->isStatic()) {
            return $instance::{$methodName}(...$realParams);
        }
        if (is_string($instance)) {
            $instance = $this->get($instance);
        }
        return $instance->{$methodName}(...$realParams);
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @return T
     */
    public function make(string $className, array $params = [])
    {
        return self::$pool[$className] = new $className(...$this->getParams($className, '__construct', $params));
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @return T
     */
    public function get(string $className, array $params = [])
    {
        return self::$pool[$className] ?? self::$pool[$className] = $this->make($className, $params);
    }

    protected function getParams(object|string $instance, string $methodName, array $params): iterable
    {
        foreach ((new Reflection($instance))->getParams($methodName) as $param) {
            $paramName = $param->getName();
            $value = $params[$paramName] ?? $this->getValue($param);
            if (is_null($value) && !$param->allowsNull()) {
                throw new SnidgetException(sprintf(
                    'Нет удалось разрешить параметр %s в %s::%s',
                    $paramName,
                    is_object($instance) ? $instance::class : $instance,
                    $methodName
                ));
            }
            yield $paramName => $value;
        }
    }

    protected function getValue(\ReflectionParameter $param): mixed
    {
        /**
         * @var ?\ReflectionNamedType $type
         */
        $type = $param->getType();
        $typeName = $type ? $type->getName() : 'mixed';
        if (class_exists($typeName)) {
            return $this->get($typeName);
        }
        return $param->isOptional() ? $param->getDefaultValue() : null;
    }
}