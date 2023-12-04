<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DataTest extends TestCase
{
    /**
     * @dataProvider additionProvider
     */
    public function testAdd(int $a, int $b, int $expected): void
    {
        $this->assertSame($expected, $a + $b);
    }

    public function additionProvider(): array
    {
        return [
            'first'  => [0, 0, 0],
            'second' => [0, 1, 1],
        ];
    }
}