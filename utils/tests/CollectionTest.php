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
        $result = $collection->map(fn($x): int => $x * 2);

        $this->assertEquals([2, 4, 6], $result->toArray());
    }

    public function testFilter(): void
    {
        $collection = new Collection([1, 2, 3, 4]);
        $result = $collection->filter(fn($x): bool => $x > 2);

        $this->assertEquals([3, 4], $result->toArray());
    }

    public function testReduce(): void
    {
        $collection = new Collection([1, 2, 3]);
        $result = $collection->reduce(fn($acc, $x): int => $acc + $x, 0);

        $this->assertEquals(6, $result);
    }

    public function testFind(): void
    {
        $collection = new Collection([1, 2, 3, 4]);

        $found = $collection->find(fn($x): bool => $x === 3);
        $this->assertEquals(3, $found);

        $notFound = $collection->find(fn($x): bool => $x === 99);
        $this->assertNull($notFound);
    }

    public function testFirstAndLast(): void
    {
        $collection = new Collection([10, 20, 30]);

        $this->assertEquals(10, $collection->first());
        $this->assertEquals(30, $collection->last());
    }

    public function testFirstAndLastEmpty(): void
    {
        $collection = new Collection([]);

        $this->assertNull($collection->first());
        $this->assertNull($collection->last());
    }

    public function testCountAndIsEmpty(): void
    {
        $collection = new Collection([1, 2, 3]);
        $this->assertEquals(3, $collection->count());
        $this->assertEquals(3, count($collection));
        $this->assertFalse($collection->isEmpty());

        $empty = new Collection([]);
        $this->assertEquals(0, $empty->count());
        $this->assertTrue($empty->isEmpty());
    }

    public function testIterable(): void
    {
        $collection = new Collection([10, 20, 30]);
        $result = [];
        foreach ($collection as $item) {
            $result[] = $item;
        }
        $this->assertEquals([10, 20, 30], $result);
    }

    public function testToArray(): void
    {
        $type1 = new SimpleType(['name' => 'Alice', 'age' => 25, 'score' => 9.0, 'active' => true]);
        $type2 = new SimpleType(['name' => 'Bob', 'age' => 30, 'score' => 8.0, 'active' => false]);
        $collection = new Collection([$type1, $type2]);

        $result = $collection->toArray();

        $this->assertCount(2, $result);
        $this->assertEquals('Alice', $result[0]['name']);
        $this->assertEquals('Bob', $result[1]['name']);
    }

    public function testEach(): void
    {
        $collection = new Collection([1, 2, 3]);
        $sum = 0;
        $collection->each(function ($item) use (&$sum): void {
            $sum += $item;
        });
        $this->assertEquals(6, $sum);
    }
}
