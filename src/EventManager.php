<?php

namespace Snidget;

class EventManager
{
    protected array $listeners = [];

    public function subscribe(string $eventName, callable $listener)
    {
        $this->listeners[$eventName][] = $listener;
    }

    public function emit(string $eventName, mixed $data)
    {
        foreach ($this->listeners[$eventName] as $listener) {
            $listener($data);
        }
    }
}