<?php

use PHPUnit\Framework\TestCase;
use Snidget\Kernel\Schema\Collection;
use Snidget\Tests\Fixtures\SimpleType;

class CollectionTest extends TestCase
{
    protected function setUp(): void
    {
        include_once __DIR__ . '/fixtures/TestTypes.php';
    }

    public function testMap(): void
    {
        $collection = new Collection([1, 2, 3]);
        $result = $collection->map(fn(int $x): int => $x * 2);

        self::assertEquals([2, 4, 6], $result->toArray());
    }

    public function testFilter(): void
    {
        $collection = new Collection([1, 2, 3, 4]);
        $result = $collection->filter(fn(int $x): bool => $x > 2);

        self::assertEquals([3, 4], $result->toArray());
    }

    public function testReduce(): void
    {
        $collection = new Collection([1, 2, 3]);
        $result = $collection->reduce(fn(int $acc, int $x): int => $acc + $x, 0);

        self::assertEquals(6, $result);
    }

    public function testFind(): void
    {
        $collection = new Collection([1, 2, 3, 4]);

        $found = $collection->find(fn(int $x): bool => $x === 3);
        self::assertEquals(3, $found);

        $notFound = $collection->find(fn(int $x): bool => $x === 99);
        self::assertNull($notFound);
    }

    public function testFirstAndLast(): void
    {
        $collection = new Collection([10, 20, 30]);

        self::assertEquals(10, $collection->first());
        self::assertEquals(30, $collection->last());
    }

    public function testFirstAndLastEmpty(): void
    {
        $collection = new Collection([]);

        self::assertNull($collection->first());
        self::assertNull($collection->last());
    }

    public function testCountAndIsEmpty(): void
    {
        $collection = new Collection([1, 2, 3]);
        self::assertEquals(3, $collection->count());
        self::assertEquals(3, count($collection));
        self::assertFalse($collection->isEmpty());

        $empty = new Collection([]);
        self::assertEquals(0, $empty->count());
        self::assertTrue($empty->isEmpty());
    }

    public function testIterable(): void
    {
        $collection = new Collection([10, 20, 30]);
        $result = [];
        foreach ($collection as $item) {
            $result[] = $item;
        }

        self::assertEquals([10, 20, 30], $result);
    }

    public function testToArray(): void
    {
        $type1 = new SimpleType(['name' => 'Alice', 'age' => 25, 'score' => 9.0, 'active' => true]);
        $type2 = new SimpleType(['name' => 'Bob', 'age' => 30, 'score' => 8.0, 'active' => false]);
        $collection = new Collection([$type1, $type2]);

        $result = $collection->toArray();

        self::assertEquals([$type1->toArray(), $type2->toArray()], $result);
    }

    public function testEach(): void
    {
        $collection = new Collection([1, 2, 3]);
        $sum = 0;
        $collection->each(function (int $item) use (&$sum): void {
            $sum += $item;
        });
        self::assertEquals(6, $sum);
    }
}
