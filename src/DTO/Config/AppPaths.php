<?php

namespace Snidget\DTO\Config;

class AppPaths
{
    protected string $controllerPath = 'HTTP/Controller';
    protected string $middlewarePath = 'HTTP/Middleware';
    protected string $dtoPath = 'DTO/API';
    public bool $displayAllErrors = true;

    public function __construct(
        protected string $appPath
    ) {
    }

    public function getControllerPaths(): array
    {
        return $this->getPathByType($this->controllerPath);
    }

    public function getMiddlewarePaths(): array
    {
        return $this->getPathByType($this->middlewarePath);
    }

    public function getDtoPaths(): array
    {
        return $this->getPathByType($this->dtoPath);
    }

    protected function getPathByType(string $dir): array
    {
        return [
            $this->appPath . '/' . $dir,
            ...(glob($this->appPath . '/Box/*/' . $dir) ?: []),
        ];
    }
}
