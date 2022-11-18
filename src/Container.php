<?php

namespace Snidget;

use Psr\Container\ContainerInterface;
use ReflectionNamedType;
use ReflectionParameter;
use Snidget\Exception\SnidgetException;
use Snidget\Module\Reflection;

/**
 * interface for user-space usage
 * @template T
 */
class Container implements ContainerInterface
{
    protected array $pool = [];
    protected array $map = [];

    public function __construct()
    {
        // ссылка на себя, чтобы при запросе контейнера из контейнера возвращался сам инстанс
        $this->pool[__CLASS__] = $this;
    }

    /**
     * @param object|class-string<T> $instance
     * @throws SnidgetException
     */
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
     * @param class-string<T> $origId
     * @return T
     * @throws SnidgetException
     */
    public function make(string $origId, array $params = [])
    {
        $id = $this->map[$origId] ?? $origId;
        $id = is_callable($id) ? $id($this) : $id;
        /** @var class-string<T> $id */
        return $this->pool[$origId] = $this->pool[$id] = new $id(...$this->getParams($id, '__construct', $params));
    }

    /**
     * @param class-string<T> $id
     * @return T
     * @throws SnidgetException
     */
    public function get(string $id, array $params = [])
    {
        return $this->pool[$id] ?? $this->make($id, $params);
    }

    public function has(string $id): bool
    {
        return isset($this->pool[$id]);
    }

    public function link(string $id, callable|string|null $target = null): void
    {
        if ($target) {
            $this->map[$id] = $target;
        } else {
            unset($this->map[$id]);
        }
    }

    protected function getParams(object|string $instance, string $methodName, array $params): \Generator
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

    /**
     * @throws \ReflectionException
     * @throws SnidgetException
     */
    protected function getValue(ReflectionParameter $param): mixed
    {
        /**
         * @var ?ReflectionNamedType $type
         */
        $type = $param->getType();
        $typeName = $type ? $type->getName() : 'mixed';
        if (class_exists($typeName)) {
            return $this->get($typeName);
        }
        return $param->isOptional() ? $param->getDefaultValue() : null;
    }
}