<?php

use PHPUnit\Framework\TestCase;
use Snidget\Database\ConnectConfig;
use Snidget\Database\SQLite\Driver;
use Snidget\Database\SQLite\Table;
use Snidget\Kernel\SnidgetException;
use Snidget\Tests\Fixtures\TestSchema;

class DatabaseTest extends TestCase
{
    protected Driver $db;

    protected function setUp(): void
    {
        include_once __DIR__ . '/fixtures/TestSchema.php';
        $config = new ConnectConfig(dsn: 'sqlite::memory:');
        $this->db = new Driver($config);
    }

    protected function createTable(): Table
    {
        $table = new Table($this->db, 'test', new TestSchema());
        $table->create();
        return $table;
    }

    public function testTableCreateAndExist(): void
    {
        $table = new Table($this->db, 'test', new TestSchema());
        $this->assertFalse($table->exist());
        $table->create();
        $this->assertTrue($table->exist());
    }

    public function testInsertAndFind(): void
    {
        $table = $this->createTable();

        $data = new TestSchema(['name' => 'Alice', 'age' => 25]);
        $table->insert($data);

        $data2 = new TestSchema(['name' => 'Bob', 'age' => 30]);
        $table->insert($data2);

        $rows = $table->find();
        $this->assertCount(2, $rows);
        $this->assertEquals('Alice', $rows[0]['name']);
        $this->assertEquals('Bob', $rows[1]['name']);
    }

    public function testInsertAndRead(): void
    {
        $table = $this->createTable();

        $data = new TestSchema(['name' => 'Alice', 'age' => 25]);
        $table->insert($data);

        $row = $table->read(1);
        $this->assertEquals('Alice', $row['name']);
        $this->assertEquals(25, $row['age']);
    }

    public function testCount(): void
    {
        $table = $this->createTable();

        $this->assertEquals(0, $table->count());

        $table->insert(new TestSchema(['name' => 'Alice']));
        $this->assertEquals(1, $table->count());

        $table->insert(new TestSchema(['name' => 'Bob']));
        $this->assertEquals(2, $table->count());
    }

    public function testLike(): void
    {
        $table = $this->createTable();

        $table->insert(new TestSchema(['name' => 'Alice']));
        $table->insert(new TestSchema(['name' => 'Bob']));
        $table->insert(new TestSchema(['name' => 'Alina']));

        $results = $table->like('al', 'name');
        $this->assertCount(2, $results);
    }

    public function testInvalidTableName(): void
    {
        $this->expectException(SnidgetException::class);
        new Table($this->db, 'users; DROP TABLE', new TestSchema());
    }

    public function testInvalidFieldName(): void
    {
        $table = $this->createTable();
        $table->insert(new TestSchema(['name' => 'Alice']));

        $this->expectException(SnidgetException::class);
        $table->read(1, 'id; DROP TABLE');
    }

    public function testSqlInjectionInValues(): void
    {
        $table = $this->createTable();

        $malicious = "'; DROP TABLE test; --";
        $table->insert(new TestSchema(['name' => $malicious]));

        $this->assertTrue($table->exist());
        $rows = $table->find();
        $this->assertCount(1, $rows);
        $this->assertEquals($malicious, $rows[0]['name']);
    }

    public function testTransactionCommit(): void
    {
        $table = $this->createTable();

        $this->db->transaction(function () use ($table): void {
            $table->insert(new TestSchema(['name' => 'Alice']));
            $table->insert(new TestSchema(['name' => 'Bob']));
        });

        $this->assertEquals(2, $table->count());
    }

    public function testTransactionRollback(): void
    {
        $table = $this->createTable();

        try {
            $this->db->transaction(function () use ($table): void {
                $table->insert(new TestSchema(['name' => 'Alice']));
                throw new \RuntimeException('Ошибка');
            });
        } catch (SnidgetException) {
        }

        $this->assertEquals(0, $table->count());
    }
}
