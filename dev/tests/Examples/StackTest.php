<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\TestCase;

final class StackTest extends TestCase
{
    /** @return list<string> */
    public function testEmpty(): array
    {
        $stack = [];
        self::assertEmpty($stack);

        return $stack;
    }

    /**
     * @param list<string> $stack
     * @return list<string>
     */
    #[Depends('testEmpty')]
    public function testPush(array $stack): array
    {
        $stack[] = 'foo';
        self::assertSame('foo', $stack[count($stack) - 1]);
        self::assertNotEmpty($stack);

        return $stack;
    }

    /**
     * @param list<string> $stack
     */
    #[Depends('testPush')]
    public function testPop(array $stack): void
    {
        self::assertSame('foo', array_pop($stack));
        self::assertEmpty($stack);
    }
}
