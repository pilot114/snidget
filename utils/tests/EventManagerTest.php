<?php

use PHPUnit\Framework\TestCase;
use Snidget\Kernel\PSR\Container;
use Snidget\Kernel\PSR\Event\EventManager;
use Snidget\Kernel\PSR\Event\KernelEvent;

class EventManagerTest extends TestCase
{
    protected EventManager $eventManager;
    protected Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->eventManager = new EventManager($this->container);
    }

    public function testEmitWithoutListeners(): void
    {
        // Не должно выбрасываться исключений
        $this->eventManager->emit(KernelEvent::START);
        $this->assertTrue(true);
    }

    public function testDispatchReturnsEvent(): void
    {
        $event = KernelEvent::START;
        $result = $this->eventManager->dispatch($event);
        $this->assertSame($event, $result);
    }

    public function testDispatchReturnsObjectEvent(): void
    {
        $event = new \stdClass();
        $event->handled = false;

        $result = $this->eventManager->dispatch($event);
        $this->assertSame($event, $result);
    }

    public function testMultipleEmitsWithoutError(): void
    {
        $this->eventManager->emit(KernelEvent::START);
        $this->eventManager->emit(KernelEvent::REQUEST);
        $this->eventManager->emit(KernelEvent::RESPONSE);
        $this->eventManager->emit(KernelEvent::FINISH);
        $this->assertTrue(true);
    }
}
