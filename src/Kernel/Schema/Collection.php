<?php

namespace Snidget\Kernel\Schema;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class Collection implements Countable, IteratorAggregate
{
    public function __construct(
        protected array $items = []
    ) {}

    public function toArray(): array
    {
        return $this->map(function ($value) {
            if (!is_object($value)) {
                return $value;
            }

            return method_exists($value, 'toArray') ? $value->toArray() : $value;
        })->items;
    }

    public function map(callable $callback): self
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        return new self(array_combine($keys, $items));
    }

    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->items, $callback)));
    }

    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    public function find(callable $callback): mixed
    {
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }
        return null;
    }

    public function first(): mixed
    {
        if ($this->items === []) {
            return null;
        }
        return $this->items[array_key_first($this->items)];
    }

    public function last(): mixed
    {
        if ($this->items === []) {
            return null;
        }
        return $this->items[array_key_last($this->items)];
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            $callback($item, $key);
        }
        return $this;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
