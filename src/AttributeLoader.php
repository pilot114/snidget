<?php

namespace Snidget;

use Snidget\Attribute\Bind;
use Snidget\Attribute\Column;
use Snidget\Attribute\Route;
use Snidget\Attribute\Assert;
use Snidget\Module\Reflection;
use Snidget\Typing\Type;

class AttributeLoader
{
    static public function getAssertions(string $className): iterable
    {
        yield from (new Reflection($className))->getAttributes(Reflection::ATTR_PROPERTY, Assert::class);
    }

    static public function getBinds(string $classPath): iterable
    {
        foreach (psrIterator($classPath) as $className) {
            $ref = new Reflection($className);
            yield from $ref->getAttributes(Reflection::ATTR_CLASS, Bind::class);
            yield from $ref->getAttributes(Reflection::ATTR_METHOD, Bind::class);
        }
    }

    static function getBindsByAction(string $controllerName, string $actionName): iterable
    {
        $ref = new Reflection($controllerName);
        foreach ($ref->getAttributes(Reflection::ATTR_CLASS, Bind::class) as $fqn => $attribute) {
            yield $fqn => $attribute;
        }
        foreach ($ref->getAttributes(Reflection::ATTR_METHOD, Bind::class) as $fqn => $attribute) {
            $method = explode('::', $fqn)[1];
            if ($method === $actionName) {
                yield $fqn => $attribute;
            }
        }
    }

    static public function getRoutes(string $controllerPath): iterable
    {
        foreach (psrIterator($controllerPath) as $className) {
            $ref = new Reflection($className);
            $prefix = $ref->getAttributes(Reflection::ATTR_CLASS, Route::class)->current()?->getPrefix();
            foreach ($ref->getAttributes(Reflection::ATTR_METHOD, Route::class) as $fqn => $attribute) {
                $regex = $attribute->getRegex();
                $regex = ($prefix && $regex) ? "$prefix/$regex" : ($prefix ?? $regex);
                yield $regex => $fqn;
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
            $propName = explode('::', $fqn)[1];
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