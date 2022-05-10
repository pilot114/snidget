<?php

namespace Snidget;

use Snidget\Module\Reflection;
use LogicException;

class Container
{
    protected array $pool = [];

    protected function getParams(object|string $instance, string $methodName, array $params): iterable
    {
        foreach ((new Reflection($instance))->getParams($methodName) as $param) {
            $paramName = $param->getName();

            $value = $params[$paramName] ?? null;
            if (!$value) {
                $typeName = $param->getType()->getName();
                $value = class_exists($typeName) ? $this->get($typeName) : $param->getDefaultValue();
            }
            if (is_null($value) && !$param->allowsNull()) {
                throw new LogicException(sprintf(
                    'Нет удалось разрешить параметр %s в %s::%s',
                    $paramName,
                    is_object($instance) ? $instance::class : $instance,
                    $methodName
                ));
            }
            yield $paramName => $value;
        }
    }

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
}