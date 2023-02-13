<?php

namespace Snidget\Schema\Config;

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
        return $this->getPathByType($this->commandPath);
    }

    public function getControllerPaths(): array
    {
        return $this->getPathByType($this->controllerPath);
    }

    public function getMiddlewarePaths(): array
    {
        return $this->getPathByType($this->middlewarePath);
    }

    public function getSchemaPaths(): array
    {
        return $this->getPathByType($this->schemaPath);
    }

    protected function getPathByType(string $dir): array
    {
        return [
            $this->appPath . '/' . $dir,
            ...(glob($this->appPath . '/Box/*/' . $dir) ?: []),
        ];
    }
}
