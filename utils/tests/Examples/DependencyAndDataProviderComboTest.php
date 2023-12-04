<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DependencyAndDataProviderComboTest extends TestCase
{
    public function provider1(): array
    {
        return [
            ['provider11'],
            ['provider12'],
        ];
    }

    public function provider2(): array
    {
        return [
            ['provider21'],
            ['provider22'],
        ];
    }

    public function testProducerFirst(): string
    {
        $this->assertTrue(true);

        return 'first';
    }

    public function testProducerSecond(): string
    {
        $this->assertTrue(true);

        return 'second';
    }

    /**
     * @depends testProducerFirst
     * @depends testProducerSecond
     * @dataProvider provider1
     * @dataProvider provider2
     */
    public function testConsumer(string $fromProvider, string $first, string $second): void
    {
        $this->assertContains($fromProvider, [
            'provider11',
            'provider12',
            'provider21',
            'provider22',
        ]);
        $this->assertSame('first', $first);
        $this->assertSame('second', $second);
    }
}