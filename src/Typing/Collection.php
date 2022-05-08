<?php

namespace Wshell\Snidget\Typing;

class Collection
{
    public function __construct(
        protected array $items = []
    ){}

    public function map(callable $callback): static
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        return new static(array_combine($keys, $items));
    }

    public function toArray(): array
    {
        return $this->map(function ($value) {
            if (!is_object($value)) {
                return $value;
            }

            return method_exists($value, 'toArray') ? $value->toArray() : $value;
        })->items;
    }

    public function transpose(): static
    {
        $array = [];
        foreach ($this->items as $row => $item) {
            foreach ($item as $col => $value) {
                $array[$col][$row] = $value;
            }
        }
        return new static($array);
    }
}
