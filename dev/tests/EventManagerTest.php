<?php

use PHPUnit\Framework\TestCase;
use Snidget\Kernel\PSR\Container;
use Snidget\Kernel\PSR\Event\EventManager;
use Snidget\Kernel\PSR\Event\KernelEvent;
use Snidget\Tests\Fixtures\FixtureListener;
use Snidget\Tests\Fixtures\StopEvent;

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
        $this->expectNotToPerformAssertions();
        $this->eventManager->emit(KernelEvent::START);
    }

    public function testDispatchReturnsEvent(): void
    {
        $event = KernelEvent::START;
        $result = $this->eventManager->dispatch($event);
        self::assertSame($event, $result);
    }

    public function testDispatchReturnsObjectEvent(): void
    {
        $event = new \stdClass();
        $event->handled = false;

        $result = $this->eventManager->dispatch($event);
        self::assertSame($event, $result);
    }

    public function testMultipleEmitsWithoutError(): void
    {
        $this->expectNotToPerformAssertions();
        $this->eventManager->emit(KernelEvent::START);
        $this->eventManager->emit(KernelEvent::REQUEST);
        $this->eventManager->emit(KernelEvent::RESPONSE);
        $this->eventManager->emit(KernelEvent::FINISH);
    }

    public function testEmitCallsListenerWithData(): void
    {
        include_once __DIR__ . '/fixtures/Handlers.php';
        $manager = new EventManager($this->container, ['START' => [FixtureListener::class . '::onData']]);

        $manager->emit(KernelEvent::START, 'payload');
        self::assertSame('payload', array_last(FixtureListener::$received));
    }

    public function testDispatchStopsPropagation(): void
    {
        include_once __DIR__ . '/fixtures/Handlers.php';
        $listener = FixtureListener::class . '::onEvent';
        $manager = new EventManager($this->container, [StopEvent::class => [$listener, $listener]]);

        $event = $manager->dispatch(new StopEvent());
        self::assertSame(1, $event instanceof StopEvent ? $event->calls : 0);
    }
}
