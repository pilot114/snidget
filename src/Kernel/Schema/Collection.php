<?php

namespace Snidget\Kernel\Schema;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

/**
 * @template T
 * @implements IteratorAggregate<array-key, T>
 */
class Collection implements Countable, IteratorAggregate
{
    /**
     * @param array<T> $items
     */
    public function __construct(
        protected array $items = []
    ) {
    }

    /** @return array<mixed> */
    public function toArray(): array
    {
        return $this->map(function ($value) {
            if (!is_object($value)) {
                return $value;
            }

            return method_exists($value, 'toArray') ? $value->toArray() : $value;
        })->items;
    }

    /**
     * @template R
     * @param callable(T, array-key): R $callback
     * @return self<R>
     */
    #[\NoDiscard('коллекция неизменяемая, результат — в новой коллекции')]
    public function map(callable $callback): self
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        return new self(array_combine($keys, $items));
    }

    /**
     * @param callable(T): bool $callback
     * @return self<T>
     */
    #[\NoDiscard('коллекция неизменяемая, результат — в новой коллекции')]
    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->items, $callback)));
    }

    /**
     * @template R
     * @param callable(R, T): R $callback
     * @param R $initial
     * @return R
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * @param callable(T): bool $callback
     * @return T|null
     */
    public function find(callable $callback): mixed
    {
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    /** @return T|null */
    public function first(): mixed
    {
        if ($this->items === []) {
            return null;
        }

        return array_first($this->items);
    }

    /** @return T|null */
    public function last(): mixed
    {
        if ($this->items === []) {
            return null;
        }

        return array_last($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * @param callable(T, array-key): mixed $callback
     * @return $this
     */
    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    /** @return ArrayIterator<array-key, T> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
