<?php

use PHPUnit\Framework\TestCase;
use Snidget\Kernel\ErrorHandler;

class ErrorHandlerTest extends TestCase
{
    public function testErrorIsLoggedAtOnce(): void
    {
        $log = (string)tempnam(sys_get_temp_dir(), 'log');
        $previousLog = (string)ini_set('error_log', $log);
        new ErrorHandler()->onError(new RuntimeException('some error'));
        ini_set('error_log', $previousLog);

        $output = (string)file_get_contents($log);
        unlink($log);
        self::assertStringContainsString('some error', $output);
    }
}
