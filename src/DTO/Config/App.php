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

    public function getControllerPath(): string
    {
        return $this->appPath . '/' . $this->controllerPath;
    }

    public function getMiddlewarePath(): string
    {
        return $this->appPath . '/' . $this->middlewarePath;
    }

    public function getDtoPath(): string
    {
        return $this->appPath . '/' . $this->dtoPath;
    }
}
