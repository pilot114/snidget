<?php

namespace Snidget\Kernel\PSR;

use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface
{
    protected array $log = [];

    public function __toString(): string
    {
        return implode("\n", array_map(fn($x) => sprintf("%s: %s %s (%s)", ...$x), $this->log));
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->push(LogLevel::EMERGENCY, (string)$message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->push(LogLevel::ALERT, (string)$message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->push(LogLevel::CRITICAL, (string)$message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->push(LogLevel::ERROR, (string)$message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->push(LogLevel::WARNING, (string)$message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->push(LogLevel::NOTICE, (string)$message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->push(LogLevel::INFO, (string)$message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->push(LogLevel::DEBUG, (string)$message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->push($level, (string)$message, $context);
    }

    protected function push(LogLevel $level, string $message, array $context = []): void
    {
        $this->log[] = [microtime(true), $level->name, $message, json_encode($context)];
    }
}
