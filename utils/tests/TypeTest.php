<?php

use PHPUnit\Framework\TestCase;
use Snidget\Tests\Fixtures\SimpleType;
use Snidget\Tests\Fixtures\AddressType;
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

        $this->assertEquals($data, $result);
    }

    public function testToArrayIncludesScalars(): void
    {
        $type = new SimpleType(['name' => 'Bob', 'age' => 30, 'score' => 7.5, 'active' => false]);
        $result = $type->toArray();

        $this->assertSame('Bob', $result['name']);
        $this->assertSame(30, $result['age']);
        $this->assertSame(7.5, $result['score']);
        $this->assertSame(false, $result['active']);
    }

    public function testToArrayWithNestedType(): void
    {
        $type = new NestedType([
            'name' => 'Alice',
            'address' => ['city' => 'Moscow', 'street' => 'Main'],
        ]);
        $result = $type->toArray();

        $this->assertEquals('Alice', $result['name']);
        $this->assertEquals(['city' => 'Moscow', 'street' => 'Main'], $result['address']);
    }

    public function testToArrayWithCollection(): void
    {
        $type = new CollectionType([
            'title' => 'Group',
            'items' => [
                ['name' => 'Alice', 'age' => 25, 'score' => 9.0, 'active' => true],
                ['name' => 'Bob', 'age' => 30, 'score' => 8.0, 'active' => false],
            ],
        ]);
        $result = $type->toArray();

        $this->assertEquals('Group', $result['title']);
        $this->assertCount(2, $result['items']);
        $this->assertEquals('Alice', $result['items'][0]['name']);
    }

    public function testJsonSerialize(): void
    {
        $data = ['name' => 'Alice', 'age' => 25, 'score' => 9.5, 'active' => true];
        $type = new SimpleType($data);

        $json = json_encode($type);
        $decoded = json_decode($json, true);

        $this->assertEquals($data, $decoded);
    }

    public function testFromArrayTypeMismatch(): void
    {
        $this->expectException(TypeError::class);
        new SimpleType(['name' => 'Alice', 'age' => 'not_a_number']);
    }
}
