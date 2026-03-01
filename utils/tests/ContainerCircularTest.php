<?php

use PHPUnit\Framework\TestCase;
use Snidget\Kernel\PSR\Container;
use Snidget\Kernel\SnidgetException;
use Snidget\Tests\Fixtures\CircularA;
use Snidget\Tests\Fixtures\CircularB;
use Snidget\Tests\Fixtures\DeepA;
use Snidget\Tests\Fixtures\NonCircularA;

class ContainerCircularTest extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        include_once __DIR__ . '/fixtures/CircularDeps.php';
        $this->container = new Container();
    }

    public function testCircularDependencyThrows(): void
    {
        $this->expectException(SnidgetException::class);
        $this->expectExceptionMessageMatches('/циклическая зависимость/i');
        $this->container->get(CircularA::class);
    }

    public function testDeepCircularChain(): void
    {
        $this->expectException(SnidgetException::class);
        $this->expectExceptionMessageMatches('/циклическая зависимость/i');
        $this->container->get(DeepA::class);
    }

    public function testNonCircularDeepResolution(): void
    {
        $result = $this->container->get(NonCircularA::class);
        $this->assertInstanceOf(NonCircularA::class, $result);
        $this->assertEquals('leaf', $result->b->c->value);
    }
}
