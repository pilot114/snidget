<?php

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Snidget\Kernel\PSR\Container;
use Snidget\Kernel\SnidgetException;

class ContainerTest extends TestCase
{
    protected Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
        include_once __DIR__ . '/fixtures/ClassesAndInterfaces.php';
    }

    /**
     * @return Generator<array{class-string<Animal>, array{genome: int}, string|Closure(): string|null}>
     */
    protected static function getClassesAndInterfaces(): Generator
    {
        yield [Chameleon::class, ['genome' => 1], null];
        yield [Chameleon::class, ['genome' => 2], null];
        yield [Chameleon::class, ['genome' => 3], null];
        yield [Iguana::class,    ['genome' => 4], null];
        yield [Animal::class,    ['genome' => 5], Chameleon::class];
        yield [Reptile::class,   ['genome' => 6], Chameleon::class];
        yield [Lizard::class,    ['genome' => 7], fn(): string => Iguana::class];
    }

    /**
     * @return Generator<array{class-string<Animal>, array{genome: int}, string|Closure(): string|null, 'get'|'make'}>
     */
    public static function getClassesAndInterfacesByMethod(): Generator
    {
        foreach (self::getClassesAndInterfaces() as [$className, $params, $target]) {
            yield [$className, $params, $target, 'get'];
            yield [$className, $params, $target, 'make'];
        }
    }

    /**
     * @return Generator<array{'get'|'make', int}>
     */
    public static function getInstanceCounts(): Generator
    {
        yield ['get', 2];
        yield ['make', 7];
    }

    /**
     * @return Generator<array{class-string<Animal>, array{genome: int}, string|Closure(): string|null, string}>
     */
    public static function getMethods(): Generator
    {
        foreach (self::getClassesAndInterfaces() as [$className, $params, $target]) {
            yield [$className, $params, $target, 'getGenome'];
            yield [$className, $params, $target, 'getName'];
        }
    }

    public function testNotResolveParam(): void
    {
        $this->expectException(SnidgetException::class);
        $this->container->get(Chameleon::class);
    }

    /**
     * @param class-string<Animal> $className
     * @param array{genome: int} $params
     * @param string|Closure(): string|null $target
     * @param 'get'|'make' $method
     */
    #[DataProvider('getClassesAndInterfacesByMethod')]
    public function testHas(string $className, array $params, string|Closure|null $target, string $method): void
    {
        self::assertFalse($this->container->has($className), 'Контейнер не должен содержать этот класс');

        if ($target !== null) {
            $this->container->link($className, $target);
        }

        $this->resolve($this->container, $method, $className, $params);

        self::assertTrue($this->container->has($className), 'Контейнер должен содержать этот класс');
    }

    /**
     * @param class-string<Animal> $className
     * @param array{genome: int} $params
     * @param string|Closure(): string|null $target
     * @param 'get'|'make' $method
     */
    #[DataProvider('getClassesAndInterfacesByMethod')]
    public function testPull(string $className, array $params, string|Closure|null $target, string $method): void
    {
        if ($target !== null) {
            $this->container->link($className, $target);
        }

        $class = $this->resolve($this->container, $method, $className, $params);

        self::assertInstanceOf($className, $class, 'Контейнер не вернул запрошенный класс');
        self::assertEquals($class->getGenome(), $params['genome'], 'К классу не применились нужные параметры');
    }

    /**
     * @param 'get'|'make' $method
     */
    #[DataProvider('getInstanceCounts')]
    public function testCache(string $method, int $instanceCount): void
    {
        $container = new Container();
        $ids = [];
        foreach (self::getClassesAndInterfaces() as [$className, $params, $target]) {
            if ($target !== null) {
                $container->link($className, $target);
            }

            $ids[] = $this->resolve($container, $method, $className, $params)->getGenome();
        }

        self::assertCount($instanceCount, array_unique($ids), 'Неверное кол-во инстансов');
    }

    /**
     * @param class-string<Animal> $className
     * @param array{genome: int} $params
     * @param string|Closure(): string|null $target
     */
    #[DataProvider('getMethods')]
    public function testCall(string $className, array $params, string|Closure|null $target, string $method): void
    {
        if ($target !== null) {
            $this->container->link($className, $target);
        }

        $class = $this->container->get($className, $params);
        // getName статический: Reptile::getName() возвращает static::class
        $expected = $method === 'getGenome' ? $class->getGenome() : $class::class;

        self::assertEquals($expected, $this->container->call($class, $method), 'Неверный результат вызова');
        self::assertEquals($expected, $this->container->call($className, $method), 'Неверный результат вызова');
    }

    public function testLink(): void
    {
        $params = ['genome' => 1];

        $this->container->link(Lizard::class, Chameleon::class);

        self::assertInstanceOf(
            Chameleon::class,
            $this->container->get(Lizard::class, $params),
            'Неверное разрешение класса'
        );

        $this->container->link(Lizard::class, Iguana::class);

        self::assertInstanceOf(
            Iguana::class,
            $this->container->get(Lizard::class, $params),
            'Неверное разрешение класса'
        );

        $this->container->link(Lizard::class);

        $this->expectException(SnidgetException::class);
        $this->container->get(Lizard::class, $params);
    }

    /**
     * @param 'get'|'make' $method
     * @param class-string<Animal> $className
     * @param array<string, mixed> $params
     */
    private function resolve(Container $container, string $method, string $className, array $params): Animal
    {
        return match ($method) {
            'get'  => $container->get($className, $params),
            'make' => $container->make($className, $params),
        };
    }
}
