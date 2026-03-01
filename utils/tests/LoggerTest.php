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
        $this->assertCount(8, $log);

        $levels = array_column($log, 1);
        $this->assertContains('EMERGENCY', $levels);
        $this->assertContains('ALERT', $levels);
        $this->assertContains('CRITICAL', $levels);
        $this->assertContains('ERROR', $levels);
        $this->assertContains('WARNING', $levels);
        $this->assertContains('NOTICE', $levels);
        $this->assertContains('INFO', $levels);
        $this->assertContains('DEBUG', $levels);
    }

    public function testLogWithContext(): void
    {
        $logger = new Logger();
        $context = ['user' => 'Alice', 'action' => 'login'];
        $logger->info('User logged in', $context);

        $log = $logger->getLog();
        $this->assertCount(1, $log);
        $this->assertEquals(json_encode($context), $log[0][3]);
    }

    public function testGenericLogMethod(): void
    {
        $logger = new Logger();
        $logger->log(LogLevel::ERROR, 'error message');

        $log = $logger->getLog();
        $this->assertCount(1, $log);
        $this->assertEquals('ERROR', $log[0][1]);
        $this->assertEquals('error message', $log[0][2]);
    }

    public function testGenericLogMethodWithStringLevel(): void
    {
        $logger = new Logger();
        $logger->log('WARNING', 'warning message');

        $log = $logger->getLog();
        $this->assertCount(1, $log);
        $this->assertEquals('WARNING', $log[0][1]);
    }

    public function testToString(): void
    {
        $logger = new Logger();
        $logger->info('test message');

        $output = (string) $logger;
        $this->assertStringContainsString('INFO', $output);
        $this->assertStringContainsString('test message', $output);
    }
}
