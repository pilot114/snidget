<?php

namespace Snidget;

use Snidget\Attribute\Column;
use Snidget\Attribute\Route;
use Snidget\Module\Reflection;
use Snidget\Typing\Type;

class AttributeLoader
{
    public function __construct(
        protected string $controllerPath,
        protected string $controllerNamespace
    ){}

    /**
     * TODO: how describe item array type of Generator?
     */
    public function getRoutes(): iterable
    {
        foreach (glob($this->controllerPath . '/*') as $controller) {
            preg_match("#/(?<className>\w+)\.php#i", $controller, $matches);
            $className = $this->controllerNamespace . $matches['className'];
            $ref = new Reflection($className);
            foreach ($ref->getAttributes(Reflection::ATTR_METHOD, Route::class) as $methodName => $refAttribute) {
                yield $refAttribute->newInstance()->getRegex() => [$className, $methodName];
            }
        }
    }

    static public function getDbTypeDefinition(string $className): string
    {
        $definitions = [];
        $ref = new Reflection($className);
        foreach ($ref->getAttributes(Reflection::ATTR_PROPERTY, Column::class) as $refAttribute) {
            $definitions[] = $refAttribute->newInstance()->getDefinition();
        }
        return implode(', ', $definitions);
    }

    static public function getDbTypeInsertDefinition(string $className, Type $data): string
    {
        $definitions = [];
        $ref = new Reflection($className);
        foreach ($ref->getAttributes(Reflection::ATTR_PROPERTY, Column::class) as $propName => $refAttribute) {
            $definitions[$propName] = $refAttribute->newInstance()->getInsertDefinition($data);
        }
        $definitions = array_filter($definitions);
        return sprintf(
            '(%s) values (%s)',
            implode(', ', array_keys($definitions)),
            implode(', ', array_values($definitions)),
        );
    }
}