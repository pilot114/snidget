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
            ...glob($this->appPath . '/Box/*/' . $this->controllerPath),
            $this->appPath . '/' . $this->controllerPath,
        ];
    }

    public function getMiddlewarePaths(): array
    {
        return [
            ...glob($this->appPath . '/Box/*/' . $this->middlewarePath),
            $this->appPath . '/' . $this->middlewarePath,
        ];
    }

    public function getDtoPaths(): array
    {
        return [
            ...glob($this->appPath . '/Box/*/' . $this->dtoPath),
            $this->appPath . '/' . $this->dtoPath,
        ];
    }
}
