<?php

namespace Snidget;

use Snidget\Exception\SnidgetException;
use Snidget\Module\Reflection;

class Container
{
    protected array $pool = [];

    public function call(object $instance, string $methodName, array $params = []): mixed
    {
        return $instance->{$methodName}(...$this->getParams($instance, $methodName, $params));
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @return T
     */
    public function make(string $className, array $params = [])
    {
        $this->pool[$className] = new $className(...$this->getParams($className, '__construct', $params));
        return $this->pool[$className];
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @return T
     */
    public function get(string $className, array $params = [])
    {
        if (isset($this->pool[$className])) {
            return $this->pool[$className];
        }
        $this->pool[$className] = $this->make($className, $params);
        return $this->pool[$className];
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

    protected function getValue(\ReflectionParameter $param)
    {
        $typeName = $param->getType()->getName();
        if (class_exists($typeName)) {
            return $this->get($typeName);
        }
        return $param->isOptional() ? $param->getDefaultValue() : null;
    }
}