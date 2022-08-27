<?php

namespace Snidget\Module;

class PrintFormat
{
    /**
     * Вывод миллисекунд в удобочитаемом формате
     */
    static public function millisecondPrint(int $x): string
    {
        $ms = $x % 1_000;
        $s = floor(($x % 60_000) / 1_000);
        $m = floor(($x % 3600_000) / 60_000);
        $h = floor(($x % 86400_000) / 3600_000);
        $d = floor(($x % 2592_000_000) / 86_400_000);
        $M = floor($x / 2_592_000_000);
        $result = [];
        $M && $result[] = "$M months";
        $d && $result[] = "$d days";
        $h && $result[] = "$h hours";
        $m && $result[] = "$m minutes";
        $s && $result[] = "$s seconds";
        $ms && $result[] = "$ms milliseconds";

        return implode(' ', $result);
    }
}