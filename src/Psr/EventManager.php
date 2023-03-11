<?php

namespace Snidget\Psr;

use Snidget\AttributeLoader;
use UnitEnum;

class EventManager
{
    public function __construct(
        protected Container $container,
        protected array $listeners = [],
    ){}

    public function register(string $appPath): void
    {
        foreach (AttributeLoader::getListeners([$appPath]) as $fqn => $listener) {
            $this->listeners[$listener->getEvent()->name][] = $fqn;
        }
    }

    public function emit(UnitEnum $event, mixed $data = null): void
    {
        foreach ($this->listeners[$event->name] ?? [] as $listener) {
            $this->container->get(Logger::class)->notice("event [$event->name] dispatch listener: $listener");
            [$class, $method] = explode('::', $listener);
            $this->container->call($class, $method, ['data' => $data]);
        }
    }
}
