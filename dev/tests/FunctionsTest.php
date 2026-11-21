<?php

use PHPUnit\Framework\TestCase;

class FunctionsTest extends TestCase
{
    public function testDumpPrintsEachValue(): void
    {
        $this->expectOutputString("text\nArray\n(\n    [0] => 1\n)\n\n");
        dump('text', [1]);
    }

    public function testMillisecondPrintSkipsZeroUnits(): void
    {
        self::assertSame('1 days 2 sec 3 msec', millisecondPrint(86_400_000 + 2_003));
        self::assertSame('', millisecondPrint(0));
    }
}
