<?php

use PHPUnit\Framework\TestCase;
use Snidget\Tests\Fixtures\SimpleType;
use Snidget\Tests\Fixtures\NestedType;
use Snidget\Tests\Fixtures\CollectionType;

class TypeTest extends TestCase
{
    protected function setUp(): void
    {
        include_once __DIR__ . '/fixtures/TestTypes.php';
    }

    public function testFromArrayAndToArray(): void
    {
        $data = ['name' => 'Alice', 'age' => 25, 'score' => 9.5, 'active' => true];
        $type = new SimpleType($data);
        $result = $type->toArray();

        self::assertEquals($data, $result);
    }

    public function testToArrayIncludesScalars(): void
    {
        $type = new SimpleType(['name' => 'Bob', 'age' => 30, 'score' => 7.5, 'active' => false]);
        $result = $type->toArray();

        self::assertSame('Bob', $result['name']);
        self::assertSame(30, $result['age']);
        self::assertSame(7.5, $result['score']);
        self::assertSame(false, $result['active']);
    }

    public function testToArrayWithNestedType(): void
    {
        $type = new NestedType([
            'name' => 'Alice',
            'address' => ['city' => 'Moscow', 'street' => 'Main'],
        ]);
        $result = $type->toArray();

        self::assertEquals('Alice', $result['name']);
        self::assertEquals(['city' => 'Moscow', 'street' => 'Main'], $result['address']);
    }

    public function testToArrayWithCollection(): void
    {
        $items = [
            ['name' => 'Alice', 'age' => 25, 'score' => 9.0, 'active' => true],
            ['name' => 'Bob', 'age' => 30, 'score' => 8.0, 'active' => false],
        ];
        $type = new CollectionType(['title' => 'Group', 'items' => $items]);
        $result = $type->toArray();

        self::assertEquals('Group', $result['title']);
        self::assertEquals($items, $result['items']);
    }

    public function testJsonSerialize(): void
    {
        $data = ['name' => 'Alice', 'age' => 25, 'score' => 9.5, 'active' => true];
        $type = new SimpleType($data);

        $json = json_encode($type, JSON_THROW_ON_ERROR);
        $decoded = json_decode($json, true);

        self::assertEquals($data, $decoded);
    }

    public function testFromArrayTypeMismatch(): void
    {
        $this->expectException(TypeError::class);
        new SimpleType(['name' => 'Alice', 'age' => 'not_a_number']);
    }
}
