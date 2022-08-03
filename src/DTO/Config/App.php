<?php

namespace Snidget\DTO\Config;

class App
{
    protected string $controllerPath = 'HTTP/Controller';
    protected string $middlewarePath = 'HTTP/Middleware';
    protected string $dtoPath = 'DTO/API';
    public bool $displayAllErrors = true;

    public function __construct(
        protected string $appPath
    ){}

    public function getControllerPaths(): array
    {
        return [
            $this->appPath . '/' . $this->controllerPath,
            ...glob($this->appPath . '/Box/*/' . $this->controllerPath),
        ];
    }

    public function getMiddlewarePaths(): array
    {
        return [
            $this->appPath . '/' . $this->middlewarePath,
            ...glob($this->appPath . '/Box/*/' . $this->middlewarePath),
        ];
    }

    public function getDtoPaths(): array
    {
        return [
            $this->appPath . '/' . $this->dtoPath,
            ...glob($this->appPath . '/Box/*/' . $this->dtoPath),
        ];
    }
}
