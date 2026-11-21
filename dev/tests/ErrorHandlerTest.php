<?php

use PHPUnit\Framework\TestCase;
use Snidget\Kernel\ErrorHandler;

class ErrorHandlerTest extends TestCase
{
    public function testShutdownLogsCollectedAndLastErrors(): void
    {
        $log = (string)tempnam(sys_get_temp_dir(), 'log');
        $previousLog = (string)ini_set('error_log', $log);
        set_error_handler(static fn(): bool => false);
        @trigger_error('last error', E_USER_NOTICE);
        restore_error_handler();

        $handler = new ErrorHandler();
        $handler->onError(new RuntimeException('collected error'));
        $handler->onShutdown();
        ini_set('error_log', $previousLog);

        $output = (string)file_get_contents($log);
        unlink($log);
        self::assertStringContainsString('collected error', $output);
        self::assertStringContainsString('last error', $output);
    }
}
