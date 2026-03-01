<?php

use PHPUnit\Framework\TestCase;
use Snidget\Kernel\Assert;
use Snidget\Tests\Fixtures\AssertableType;

class AssertTest extends TestCase
{
    protected function setUp(): void
    {
        include_once __DIR__ . '/fixtures/AssertableType.php';
    }

    public function testMinMax(): void
    {
        $assert = new Assert(min: 5, max: 10);

        $this->assertNotEmpty($assert->check(3));
        $this->assertNotEmpty($assert->check(15));
        $this->assertEmpty($assert->check(7));
    }

    public function testMinLengthMaxLength(): void
    {
        $assert = new Assert(minLength: 3, maxLength: 10);

        $this->assertNotEmpty($assert->check('ab'));
        $this->assertNotEmpty($assert->check('this string is way too long'));
        $this->assertEmpty($assert->check('hello'));
    }

    public function testPatternValidation(): void
    {
        $assert = new Assert(pattern: '/^\d{3}$/');

        $this->assertNotEmpty($assert->check('abc'));
        $this->assertNotEmpty($assert->check('12'));
        $this->assertEmpty($assert->check('123'));
    }

    public function testNotBlank(): void
    {
        $assert = new Assert(notBlank: true);

        $this->assertNotEmpty($assert->check(''));
        $this->assertNotEmpty($assert->check(null));
        $this->assertEmpty($assert->check('not blank'));
    }

    public function testValidateType(): void
    {
        $type = new AssertableType([
            'age' => 0,
            'name' => 'ab',
            'username' => 'bad user!',
            'email' => '',
        ]);

        $errors = Assert::validateType($type);

        $this->assertArrayHasKey('age', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('username', $errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testValidTypePassesValidation(): void
    {
        $type = new AssertableType([
            'age' => 25,
            'name' => 'Alice',
            'username' => 'alice_123',
            'email' => 'alice@example.com',
        ]);

        $errors = Assert::validateType($type);
        $this->assertEmpty($errors);
    }
}
