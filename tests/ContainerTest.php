<?php

use PHPUnit\Framework\TestCase;
use Snidget\Container;
use Snidget\Exception\SnidgetException;

class ContainerTest extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
        include_once __DIR__ . '/fixtures/ClassesAndInterfaces.php';
    }

    protected function getClassesAndInterfaces(): Generator
    {
        yield [Chameleon::class, ['genome' => 1], null];
        yield [Chameleon::class, ['genome' => 2], null];
        yield [Chameleon::class, ['genome' => 3], null];
        yield [Iguana::class,    ['genome' => 4], null];
        yield [Animal::class,    ['genome' => 5], Chameleon::class];
        yield [Reptile::class,   ['genome' => 6], Chameleon::class];
        yield [Lizard::class,    ['genome' => 7], fn() => Iguana::class];
    }

    protected function getClassesAndInterfacesByMethod(): Generator
    {
        foreach ($this->getClassesAndInterfaces() as $data) {
            yield [...$data, 'get'];
            yield [...$data, 'make'];
        }
    }

    protected function getInstanceCounts(): Generator
    {
        yield ['get', 2];
        yield ['make', 7];
    }

    protected function getMethods(): Generator
    {
        foreach ($this->getClassesAndInterfaces() as $data) {
            yield [...$data, 'getGenome'];
            yield [...$data, 'getName'];
        }
    }

    public function testNotResolveParam(): void
    {
        $this->expectException(SnidgetException::class);
        $this->container->get(Chameleon::class);
    }

    /**
     * @param class-string $className
     * @dataProvider getClassesAndInterfacesByMethod
     */
    public function testHas(string $className, array $params, string|callable|null $target, string $method): void
    {
        $this->assertFalse($this->container->has($className), 'Контейнер не должен содержать этот класс');

        if ($target) {
            $this->container->link($className, $target);
        }
        $this->container->$method($className, $params);

        $this->assertTrue($this->container->has($className), 'Контейнер должен содержать этот класс');
    }

    /**
     * @param class-string $className
     * @dataProvider getClassesAndInterfacesByMethod
     */
    public function testPull(string $className, array $params, string|callable|null $target, string $method): void
    {
        if ($target) {
            $this->container->link($className, $target);
        }
        /** @var Animal $class */
        $class = $this->container->$method($className, $params);

        $this->assertInstanceOf($className,      $class,            'Контейнер не вернул запрошенный класс');
        $this->assertEquals($class->getGenome(), $params['genome'], 'К классу не применились нужные параметры');
    }

    /**
     * @dataProvider getInstanceCounts
     */
    public function testCache(string $method, int $instanceCount): void
    {
        $container = new Container();
        $ids = [];
        foreach ($this->getClassesAndInterfaces() as $case) {
            [$className, $params, $target] = $case;
            if ($target) {
                $container->link($className, $target);
            }
            $ids[] = $container->$method($className, $params)->getGenome();
        }
        $this->assertCount($instanceCount, array_unique($ids), 'Неверное кол-во инстансов');
    }

    /**
     * @dataProvider getMethods
     */
    public function testCall(string $className, array $params, string|callable|null $target, string $method): void
    {
        if ($target) {
            $this->container->link($className, $target);
        }
        $class = $this->container->get($className, $params);

        $result = $this->container->call($class, $method);
        $this->assertEquals($class->$method(), $result, 'Неверный результат вызова');

        $result = $this->container->call($className, $method);
        $this->assertEquals($class->$method(), $result, 'Неверный результат вызова');
    }

    public function testLink(): void
    {
        $data = [Lizard::class, ['genome' => 1]];

        $this->container->link(Lizard::class, Chameleon::class);

        $this->assertInstanceOf(
            Chameleon::class,
            $this->container->get(...$data),
            'Неверное разрешение класса'
        );

        $this->container->link(Lizard::class, Iguana::class);

        $this->assertInstanceOf(
            Iguana::class,
            $this->container->get(...$data),
            'Неверное разрешение класса'
        );

        $this->container->link(Lizard::class);

        $this->expectError();
        $this->container->get(...$data);
    }
}
