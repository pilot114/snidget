<?php

namespace Wshell\Snidget;
use ReflectionMethod;
use ReflectionClass;
use ReflectionParameter;

class Container
{
    protected array $pool = [];

    public function call(object $instance, string $methodName, array $params = []): mixed
    {
        $methodRef = new ReflectionMethod($instance, $methodName);

        $injectParams = [];

        foreach ($methodRef->getParameters() as $param) {
            $paramName = $param->getName();
            $value = $this->getValue($param, $paramName, $params);
            if (is_null($value) && !$param->allowsNull()) {
                throw new \LogicException(sprintf(
                    'Нет удалось разрешить параметр %s метода %s::%s',
                    $paramName,
                    $instance::class,
                    $methodName
                ));
            }
            $injectParams[$paramName] = $value;
        }

        return $instance->{$methodName}(...$injectParams);
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @param array $params
     * @return T
     */
    public function make(string $className, array $params = [])
    {
        $constructorRef = (new ReflectionClass($className))->getConstructor();
        if (!$constructorRef) {
            $this->pool[$className] = new $className();
            return $this->pool[$className];
        }

        $injectParams = [];

        foreach ($constructorRef->getParameters() as $param) {
            $paramName = $param->getName();
            $value = $this->getValue($param, $paramName, $params);
            if (is_null($value) && !$param->allowsNull()) {
                throw new \LogicException(sprintf(
                    'Нет удалось разрешить параметр %s класса %s',
                    $paramName,
                    $className
                ));
            }
            $injectParams[$paramName] = $value;
        }

        $this->pool[$className] = new $className(...$injectParams);
        return $this->pool[$className];
    }

    /**
     * @template T
     * @param class-string<T> $className
     * @param array $params
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

    protected function getValue(ReflectionParameter $param, string $paramName, array $params): mixed
    {
        if (isset($params[$paramName])) {
            return $params[$paramName];
        }
        $typeName = $param->getType()->getName();
        if (class_exists($typeName)) {
            return $this->get($typeName);
        }
        return null;
    }
}