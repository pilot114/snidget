<?php

namespace Snidget;

class EventManager
{
    protected array $listeners = [];

    public function register(string $appPath): void
    {
        foreach (AttributeLoader::getListeners($appPath) as $listener) {
//            dump($listener);
        }
        die();
    }

    public function emit(string $eventName, mixed $data): void
    {
        foreach ($this->listeners[$eventName] as $listener) {
            $listener($data);
        }
    }

    protected function subscribe(callable $listener, ?string $eventName = null): void
    {
        $this->listeners[$eventName][] = $listener;
    }
}