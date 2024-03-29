<?php

namespace Snidget\Kernel\Schema;

class AppPaths
{
    public function __construct(
        protected string $appPath,
        protected string $controllerPath = 'HTTP/Controller',
        protected string $middlewarePath = 'HTTP/Middleware',
        protected string $schemaPath = 'Schema/API',
        protected string $commandPath = 'Command',
    ) {}

    public function getCommandPaths(): array
    {
        return $this->getPathsByType($this->commandPath);
    }

    public function getControllerPaths(): array
    {
        return $this->getPathsByType($this->controllerPath);
    }

    public function getMiddlewarePaths(): array
    {
        return $this->getPathsByType($this->middlewarePath);
    }

    public function getSchemaPaths(): array
    {
        return $this->getPathsByType($this->schemaPath);
    }

    protected function getPathsByType(string $dir): array
    {
        return [
            $this->appPath . '/' . $dir,
            ...(glob($this->appPath . '/Module/*/' . $dir) ?: []),
        ];
    }
}
