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
    ) {
    }

    /** @return list<string> */
    public function getCommandPaths(): array
    {
        return $this->getPathsByType($this->commandPath);
    }

    /** @return list<string> */
    public function getControllerPaths(): array
    {
        return $this->getPathsByType($this->controllerPath);
    }

    /** @return list<string> */
    public function getMiddlewarePaths(): array
    {
        return $this->getPathsByType($this->middlewarePath);
    }

    /** @return list<string> */
    public function getSchemaPaths(): array
    {
        return $this->getPathsByType($this->schemaPath);
    }

    /** @return list<string> */
    protected function getPathsByType(string $dir): array
    {
        $modulePaths = glob($this->appPath . '/Module/*/' . $dir);
        return [$this->appPath . '/' . $dir, ...($modulePaths === false ? [] : $modulePaths)];
    }
}
