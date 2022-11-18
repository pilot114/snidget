<?php

use PHPUnit\Framework\TestCase;
use Snidget\Container;

class ContainerTest extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    protected function getClassesAndInterfaces(): \Generator
    {
        include_once __DIR__ . '/fixtures/ClassesAndInterfaces.php';
        yield [Chameleon::class, ['genome' => 1], null];
        yield [Iguania::class,   ['genome' => 2], null];
        yield [Animal::class,    ['genome' => 3], Chameleon::class];
        yield [Reptile::class,   ['genome' => 4], Chameleon::class];
        yield [Lizard::class,    ['genome' => 5], fn() => Iguania::class];
    }

    /**
     * @param class-string $className
     * @dataProvider getClassesAndInterfaces
     */
    public function testGet(string $className, array $params, string|callable|null $target): void
    {
        if ($target) {
            $this->container->link($className, $target);
        }
        $class = $this->container->get($className, $params);
        $this->assertInstanceOf($className, $class, 'Контейнер не вернул запрошенный класс');
    }

    /**
     * @param class-string $className
     * @dataProvider getClassesAndInterfaces
     */
    public function testHas(string $className, array $params, string|callable|null $target): void
    {
        $this->assertFalse($this->container->has($className), 'Контейнер не пустой');

        if ($target) {
            $this->container->link($className, $target);
        }
        $this->container->get($className, $params);

        $this->assertTrue($this->container->has($className), 'Контейнер пустой');

        // reset container
        $this->container = new Container();

        $this->assertFalse($this->container->has($className), 'Контейнер не пустой');

        if ($target) {
            $this->container->link($className, $target);
        }
        $this->container->make($className, $params);

        $this->assertTrue($this->container->has($className), 'Контейнер пустой');
    }

//    public function testCall(): void
//    {
//        $this->markTestSkipped('no');
//    }
//
//    public function testMake(): void
//    {
//        $this->markTestSkipped('no');
//    }
}
