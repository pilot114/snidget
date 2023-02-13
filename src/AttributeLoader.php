<?php

namespace Snidget;

use Snidget\Attribute\Arg;
use Snidget\Attribute\Bind;
use Snidget\Attribute\Column;
use Snidget\Attribute\Command;
use Snidget\Attribute\Listen;
use Snidget\Attribute\Route;
use Snidget\Attribute\Assert;
use Snidget\Module\Reflection;
use Snidget\Typing\Type;

class AttributeLoader
{
    public static function getAssertions(string $typeName): \Generator
    {
        yield from (new Reflection($typeName))->getAttributes(Reflection::ATTR_PROPERTY, Assert::class);
    }

    static function getBindsByAction(string $controllerName, string $actionName): \Generator
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

    public static function getDbTypeDefinition(string $typeName): string
    {
        $definitions = [];
        $ref = new Reflection($typeName);
        foreach ($ref->getAttributes(Reflection::ATTR_PROPERTY, Column::class) as $attribute) {
            $definitions[] = $attribute->getDefinition();
        }
        return implode(', ', $definitions);
    }

    public static function getDbTypeInsertDefinition(string $typeName, Type $data): string
    {
        $definitions = [];
        $ref = new Reflection($typeName);
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

    public static function getListeners(array $listenerPaths): \Generator
    {
        foreach (psrIterator($listenerPaths, true) as $className) {
            if (!class_exists($className)) {
                continue;
            }
            $ref = new Reflection($className);
            yield from $ref->getAttributes(Reflection::ATTR_METHOD, Listen::class);
        }
    }

    public static function getRoutes(array $controllerPaths): \Generator
    {
        foreach (psrIterator($controllerPaths) as $className) {
            $ref = new Reflection($className);
            $prefix = $ref->getAttributes(Reflection::ATTR_CLASS, Route::class)->current()?->getPrefix();
            foreach ($ref->getAttributes(Reflection::ATTR_METHOD, Route::class) as $fqn => $attribute) {
                $regex = $attribute->getRegex();
                $regex = ($prefix && $regex) ? "$prefix/$regex" : ($prefix ?? $regex);
                yield $regex => $fqn;
            }
        }
    }

    public static function getBinds(array $classPaths): \Generator
    {
        foreach (psrIterator($classPaths) as $className) {
            $ref = new Reflection($className);
            yield from $ref->getAttributes(Reflection::ATTR_CLASS, Bind::class);
            yield from $ref->getAttributes(Reflection::ATTR_METHOD, Bind::class);
        }
    }

    public static function getDtoInfoByCommandName(array $commandPaths, string $command, string $subCommand): array
    {
        foreach (AttributeLoader::getCommands($commandPaths) as $fqn => $attr) {
            [$class, $method] = explode('::', $fqn);
            if (str_ends_with($class, $command) && $method === $subCommand) {
                foreach ((new Reflection($class))->getParams($method) as $param) {
                    $paramTypeName = $param->getType()->getName();
                    if (is_subclass_of($paramTypeName, Type::class)) {
                        return [$class, $method, $paramTypeName, $param->getName()];
                    }
                }
            }
        }
        return [];
    }

    public static function getArgs(string $dtoName, $isOption = true): \Generator
    {
        $refDto = new Reflection($dtoName);
        foreach ($refDto->getPublicProperties() as $prop) {
            $firstAttr = $refDto->getPropAttributes($prop->getName(), Arg::class)[0]->newInstance();
            if ($isOption && $firstAttr->isOption()) {
                yield $prop => $firstAttr;
            }
            if (!$isOption && !$firstAttr->isOption()) {
                yield $prop => $firstAttr;
            }
        }
    }

    /** TODO: for info in CLI */
    public static function getCommands(array $commandPaths): \Generator
    {
        foreach (psrIterator($commandPaths) as $className) {
            $ref = new Reflection($className);
            foreach ($ref->getAttributes(Reflection::ATTR_METHOD, Command::class) as $fqn => $attribute) {
                yield $fqn => $attribute;
            }
        }
    }
}
