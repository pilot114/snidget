<?php

namespace Snidget;

use Snidget\Enum\SystemEvent;

class EventManager
{
    public function __construct(
        protected Container $container,
        protected array $listeners = [],
    ){}

    public function register(string $appPath): void
    {
        foreach (AttributeLoader::getListeners($appPath) as $fqn => $listener) {
            $this->listeners[$listener->getEvent()->name][] = $fqn;
        }
    }

    public function emit(SystemEvent $event, mixed $data = null): void
    {
        foreach ($this->listeners[$event->name] ?? [] as $listener) {
            [$class, $method] = explode('::', $listener);
            $this->container->call($class, $method, ['data' => $data]);
        }
    }
}