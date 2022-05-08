<?php

namespace Wshell\Snidget;

use Wshell\Snidget\Attribute\Column;
use Wshell\Snidget\Attribute\Route;
use ReflectionClass;
use Wshell\Snidget\Typing\Type;

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

    static public function getDbTypeDefinition(string $className): string
    {
        $definitions = [];
        foreach ((new ReflectionClass($className))->getProperties() as $prop) {
            $attribute = $prop->getAttributes(Column::class)[0] ?? null;
            if ($attribute) {
                /* @var $attrClass Column */
                $attrClass = $attribute->newInstance();
                $definitions[] = $attrClass->getDefinition();
            }
        }
        return implode(', ', $definitions);
    }

    static public function getDbTypeInsertDefinition(string $className, Type $data): string
    {
        $definitions = [];
        foreach ((new ReflectionClass($className))->getProperties() as $prop) {
            $attribute = $prop->getAttributes(Column::class)[0] ?? null;
            if ($attribute) {
                /* @var $attrClass Column */
                $attrClass = $attribute->newInstance();
                $definitions[$prop->getName()] = $attrClass->getInsertDefinition($data);
            }
        }
        $definitions = array_filter($definitions);
        return sprintf(
            '(%s) values (%s)',
            implode(', ', array_keys($definitions)),
            implode(', ', array_values($definitions)),
        );
    }
}