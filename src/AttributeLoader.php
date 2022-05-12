<?php

namespace Snidget;

use Snidget\Attribute\Bind;
use Snidget\Attribute\Column;
use Snidget\Attribute\Route;
use Snidget\Module\Reflection;
use Snidget\Typing\Type;

class AttributeLoader
{
    static public function getBinds(string $classPath, string $classNamespace): iterable
    {
        foreach (glob($classPath . '/*') as $class) {
            preg_match("#/(?<className>\w+)\.php#i", $class, $matches);
            $className = $classNamespace . $matches['className'];
            $ref = new Reflection($className);
            foreach ($ref->getAttributes(Reflection::ATTR_CLASS, Bind::class) as $fqn => $attribute) {
                yield $fqn => $attribute;
            }
            foreach ($ref->getAttributes(Reflection::ATTR_METHOD, Bind::class) as $fqn => $attribute) {
                yield $fqn => $attribute;
            }
        }
    }

    static public function getRoutes(string $controllerPath, string $controllerNamespace): iterable
    {
        foreach (glob($controllerPath . '/*') as $controller) {
            preg_match("#/(?<className>\w+)\.php#i", $controller, $matches);
            $className = $controllerNamespace . $matches['className'];
            $ref = new Reflection($className);
            foreach ($ref->getAttributes(Reflection::ATTR_METHOD, Route::class) as $fqn => $attribute) {
                yield $attribute->getRegex() => $fqn;
            }
        }
    }

    static public function getDbTypeDefinition(string $className): string
    {
        $definitions = [];
        $ref = new Reflection($className);
        foreach ($ref->getAttributes(Reflection::ATTR_PROPERTY, Column::class) as $attribute) {
            $definitions[] = $attribute->getDefinition();
        }
        return implode(', ', $definitions);
    }

    static public function getDbTypeInsertDefinition(string $className, Type $data): string
    {
        $definitions = [];
        $ref = new Reflection($className);
        foreach ($ref->getAttributes(Reflection::ATTR_PROPERTY, Column::class) as $fqn => $attribute) {
            list($className, $propName) = explode('::', $fqn);
            $definitions[$propName] = $attribute->getInsertDefinition($data);
        }
        $definitions = array_filter($definitions);
        return sprintf(
            '(%s) values (%s)',
            implode(', ', array_keys($definitions)),
            implode(', ', array_values($definitions)),
        );
    }
}