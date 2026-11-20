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

        self::assertNotEmpty($assert->check(3));
        self::assertNotEmpty($assert->check(15));
        self::assertEmpty($assert->check(7));
    }

    public function testMinLengthMaxLength(): void
    {
        $assert = new Assert(minLength: 3, maxLength: 10);

        self::assertNotEmpty($assert->check('ab'));
        self::assertNotEmpty($assert->check('this string is way too long'));
        self::assertEmpty($assert->check('hello'));
    }

    public function testStringRulesRejectNonString(): void
    {
        self::assertNotEmpty(new Assert(minLength: 4, maxLength: 24)->check(12345678));
        self::assertNotEmpty(new Assert(pattern: '/^\d{3}$/')->check(123));
        self::assertEmpty(new Assert(minLength: 4)->check(null));
    }

    public function testPatternValidation(): void
    {
        $assert = new Assert(pattern: '/^\d{3}$/');

        self::assertNotEmpty($assert->check('abc'));
        self::assertNotEmpty($assert->check('12'));
        self::assertEmpty($assert->check('123'));
    }

    public function testNotBlank(): void
    {
        $assert = new Assert(notBlank: true);

        self::assertNotEmpty($assert->check(''));
        self::assertNotEmpty($assert->check(null));
        self::assertEmpty($assert->check('not blank'));
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

        self::assertArrayHasKey('age', $errors);
        self::assertArrayHasKey('name', $errors);
        self::assertArrayHasKey('username', $errors);
        self::assertArrayHasKey('email', $errors);
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
        self::assertEmpty($errors);
    }
}
