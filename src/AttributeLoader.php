<?php

namespace Wshell\Snidget;

use Wshell\Snidget\Attribute\Route;
use ReflectionClass;

class AttributeLoader
{
    public function __construct(
        protected string $controllerPath,
        protected string $controllerNamespace
    ){}

    public function handleRoute(callable $cb): void
    {
        foreach (glob($this->controllerPath . '/*') as $controller) {
            preg_match("#/(?<className>\w+)\.php#i", $controller, $matches);
            $fqdn = $this->controllerNamespace . $matches['className'];
            foreach ((new ReflectionClass($fqdn))->getMethods() as $method) {
                $attribute = $method->getAttributes(Route::class)[0];
                list($pattern) = $attribute->getArguments();
                $cb($pattern, $fqdn, $method->getName());
            }
        }
    }
}