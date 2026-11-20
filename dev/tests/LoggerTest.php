<?php

use PHPUnit\Framework\TestCase;
use Snidget\Kernel\PSR\Log\Logger;
use Snidget\Kernel\PSR\Log\LogLevel;

class LoggerTest extends TestCase
{
    public function testAllLogLevels(): void
    {
        $logger = new Logger();
        $logger->emergency('emergency msg');
        $logger->alert('alert msg');
        $logger->critical('critical msg');
        $logger->error('error msg');
        $logger->warning('warning msg');
        $logger->notice('notice msg');
        $logger->info('info msg');
        $logger->debug('debug msg');

        $log = $logger->getLog();
        self::assertCount(8, $log);

        $levels = array_column($log, 1);
        self::assertContains('EMERGENCY', $levels);
        self::assertContains('ALERT', $levels);
        self::assertContains('CRITICAL', $levels);
        self::assertContains('ERROR', $levels);
        self::assertContains('WARNING', $levels);
        self::assertContains('NOTICE', $levels);
        self::assertContains('INFO', $levels);
        self::assertContains('DEBUG', $levels);
    }

    public function testLogWithContext(): void
    {
        $logger = new Logger();
        $context = ['user' => 'Alice', 'action' => 'login'];
        $logger->info('User logged in', $context);

        $log = $logger->getLog();
        self::assertCount(1, $log);
        self::assertEquals(json_encode($context), $log[0][3]);
    }

    public function testGenericLogMethod(): void
    {
        $logger = new Logger();
        $logger->log(LogLevel::ERROR, 'error message');

        $log = $logger->getLog();
        self::assertCount(1, $log);
        self::assertEquals('ERROR', $log[0][1]);
        self::assertEquals('error message', $log[0][2]);
    }

    public function testGenericLogMethodWithStringLevel(): void
    {
        $logger = new Logger();
        $logger->log('WARNING', 'warning message');

        $log = $logger->getLog();
        self::assertCount(1, $log);
        self::assertEquals('WARNING', $log[0][1]);
    }

    public function testToString(): void
    {
        $logger = new Logger();
        $logger->info('test message');

        $output = (string) $logger;
        self::assertStringContainsString('INFO', $output);
        self::assertStringContainsString('test message', $output);
    }
}
