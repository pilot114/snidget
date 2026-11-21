<?php

namespace Snidget\Kernel\PSR;

use Closure;
use Generator;
use Psr\Container\ContainerInterface;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use Snidget\Kernel\Reflection;
use Snidget\Kernel\SnidgetException;

class Container implements ContainerInterface
{
    /** @var array<string, object> */
    protected array $pool = [];

    /** @var array<string, string|Closure(self): string> */
    protected array $map = [];

    /** @var list<string> */
    protected array $resolving = [];

    public function __construct()
    {
        $this->pool[self::class] = $this;
    }

    /**
     * @param array<string, mixed> $params
     * @throws SnidgetException | ReflectionException
     */
    public function call(object|string $instance, string $methodName, array $params = []): mixed
    {
        $instance = is_string($instance) ? $this->getRealId($instance) : $instance;
        $realParams = $this->getParams($instance, $methodName, $params);

        if (is_string($instance) && !new Reflection($instance)->getMethod($methodName)->isStatic()) {
            $instance = $this->get($instance);
        }

        $method = [$instance, $methodName];
        if (!is_callable($method)) {
            throw new SnidgetException("Метод $methodName нельзя вызвать");
        }

        return $method(...$realParams);
    }

    /**
     * @template T of object
     * @param class-string<T> $id
     * @param array<string, mixed> $params
     * @return T
     * @throws SnidgetException | ReflectionException
     */
    public function make(string $id, array $params = []): object
    {
        /** @var T $instance */
        $instance = $this->build($id, $params);
        return $instance;
    }

    /**
     * @template T of object
     * @param string|class-string<T> $id
     * @param array<string, mixed> $params
     * @return ($id is class-string<T> ? T : mixed)
     * @throws SnidgetException | ReflectionException
     */
    public function get(string $id, array $params = []): mixed
    {
        $id = $this->getRealId($id);
        return $this->pool[$id] ?? $this->build($id, $params);
    }

    public function set(string $id, object $instance): void
    {
        $this->pool[$id] = $instance;
    }

    public function has(string $id): bool
    {
        return isset($this->pool[$this->getRealId($id)]);
    }

    /**
     * @param string|Closure(self): string|null $target class or closure returning it, null unlinks
     */
    public function link(string $id, string|Closure|null $target = null): void
    {
        if ($target === null) {
            unset($this->map[$id]);
        } else {
            $this->map[$id] = $target;
        }
    }

    /**
     * @param array<string, mixed> $params
     * @throws SnidgetException | ReflectionException
     */
    protected function build(string $origId, array $params): object
    {
        $id = $this->getRealId($origId);
        if (!class_exists($id) || new Reflection($id)->isAbstract()) {
            throw new SnidgetException("Невозможно инcтанцировать $id: это не класс или абстрактный класс");
        }

        if (in_array($id, $this->resolving, true)) {
            $chain = implode(' → ', [...$this->resolving, $id]);
            throw new SnidgetException("Обнаружена циклическая зависимость: $chain");
        }

        $this->resolving[] = $id;
        try {
            $instance = new $id(...$this->getParams($id, '__construct', $params));
            $this->pool[$origId] = $this->pool[$id] = $instance;
            return $instance;
        } finally {
            array_pop($this->resolving);
        }
    }

    protected function getRealId(string $id): string
    {
        $id = $this->map[$id] ?? $id;
        return $id instanceof Closure ? $id($this) : $id;
    }

    /**
     * @param array<string, mixed> $params
     * @return Generator<string, mixed>
     * @throws ReflectionException | SnidgetException
     */
    protected function getParams(object|string $instance, string $methodName, array $params): Generator
    {
        foreach (new Reflection($instance)->getParams($methodName) as $param) {
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
     * @throws ReflectionException | SnidgetException
     */
    protected function getValue(ReflectionParameter $param): mixed
    {
        $type = $param->getType();
        $typeName = $type instanceof ReflectionNamedType ? $type->getName() : 'mixed';
        if (class_exists($typeName) || isset($this->map[$typeName])) {
            return $this->get($typeName);
        }

        return $param->isOptional() ? $param->getDefaultValue() : null;
    }
}
