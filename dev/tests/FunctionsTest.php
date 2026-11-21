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

    public function testPsrIteratorTakesOnlyPhpFiles(): void
    {
        $dir = sys_get_temp_dir() . '/psr' . uniqid();
        mkdir($dir);
        $files = ['Real.php', 'Backup.php.bak', 'Upper.PHP'];
        array_map(fn(string $file): bool => touch("$dir/$file"), $files);

        $classes = iterator_to_array(psrIterator([$dir]), false);
        array_map(fn(string $file): bool => unlink("$dir/$file"), $files);
        rmdir($dir);

        self::assertCount(1, $classes);
        self::assertStringEndsWith('\Real', $classes[0]);
    }
}
