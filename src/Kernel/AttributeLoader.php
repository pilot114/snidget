<?php

namespace Snidget\Kernel;

use ReflectionNamedType;
use Snidget\CLI\Arg;
use Snidget\CLI\Command;
use Snidget\Database\SQLite\Column;
use Snidget\Kernel\Schema\Type as SchemaType;
use Snidget\HTTP\Bind;
use Snidget\HTTP\Route;
use Snidget\Kernel\PSR\Event\Listen;

class AttributeLoader
{
    /** @return \Generator<string, Assert> */
    public static function getAssertions(string $typeName): \Generator
    {
        yield from new Reflection($typeName)->getAttributes(Reflection::ATTR_PROPERTY, Assert::class);
    }

    /** @return \Generator<string, Bind> */
    public static function getBindsByAction(string $controllerName, string $actionName): \Generator
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

    /**
     * @return array{columns: list<string>, values: list<mixed>}
     */
    public static function getDbTypeInsertDefinition(string $typeName, SchemaType $data): array
    {
        $columns = [];
        $values = [];
        $ref = new Reflection($typeName);
        foreach ($ref->getAttributes(Reflection::ATTR_PROPERTY, Column::class) as $attribute) {
            $value = $ref->getProperty($attribute->name)->getValue($data);
            if ($value === null) {
                continue;
            }

            $columns[] = $attribute->name;
            $values[] = $value;
        }

        return ['columns' => $columns, 'values' => $values];
    }

    /**
     * @param list<string> $listenerPaths
     * @return \Generator<string, Listen>
     */
    public static function getListeners(array $listenerPaths, ?string $alias = null): \Generator
    {
        foreach (psrIterator($listenerPaths, true, $alias) as $className) {
            if (!class_exists($className)) {
                continue;
            }

            $ref = new Reflection($className);
            yield from $ref->getAttributes(Reflection::ATTR_METHOD, Listen::class);
        }
    }

    /**
     * @param list<string> $controllerPaths
     * @return \Generator<string, array{fqn: string, method: string}>
     */
    public static function getRoutes(array $controllerPaths): \Generator
    {
        foreach (psrIterator($controllerPaths) as $className) {
            $ref = new Reflection($className);
            $classRoutes = iterator_to_array($ref->getAttributes(Reflection::ATTR_CLASS, Route::class));
            $prefix = array_first($classRoutes)?->getPrefix() ?? '';
            foreach ($ref->getAttributes(Reflection::ATTR_METHOD, Route::class) as $fqn => $attribute) {
                $regex = $attribute->getRegex();
                $regex = match (true) {
                    $prefix === '' => $regex,
                    $regex === ''  => $prefix,
                    default        => "$prefix/$regex",
                };
                yield $regex => ['fqn' => $fqn, 'method' => $attribute->getMethod()];
            }
        }
    }

    /**
     * @param list<string> $classPaths
     * @return \Generator<string, Bind>
     */
    public static function getBinds(array $classPaths): \Generator
    {
        foreach (psrIterator($classPaths) as $className) {
            $ref = new Reflection($className);
            yield from $ref->getAttributes(Reflection::ATTR_CLASS, Bind::class);
            yield from $ref->getAttributes(Reflection::ATTR_METHOD, Bind::class);
        }
    }

    /**
     * Класс, метод и DTO команды (null — команда без DTO)
     *
     * @param list<string> $commandPaths
     * @return array{}|array{string, string, class-string<SchemaType>|null}
     */
    public static function getDtoInfoByCommandName(array $commandPaths, string $command, string $subCommand): array
    {
        $result = [];
        foreach (AttributeLoader::getCommands($commandPaths) as $fqn => $attr) {
            [$class, $method] = explode('::', $fqn);
            if (substr((string)strrchr($class, '\\'), 1) === $command && $method === $subCommand) {
                $result = [$class, $method, null];
                foreach (new Reflection($class)->getParams($method) as $param) {
                    $paramType = $param->getType();
                    $paramTypeName = $paramType instanceof ReflectionNamedType ? $paramType->getName() : '';
                    if (is_subclass_of($paramTypeName, SchemaType::class)) {
                        return [$class, $method, $paramTypeName];
                    }
                }
            }
        }

        return $result;
    }

    /** @return \Generator<\ReflectionProperty, Arg> */
    public static function getArgs(string $dtoName, bool $isOption = true): \Generator
    {
        $refDto = new Reflection($dtoName);
        foreach ($refDto->getPublicProperties() as $prop) {
            $attrs = $refDto->getPropAttributes($prop->getName(), Arg::class);
            if ($attrs === []) {
                continue;
            }

            $firstAttr = $attrs[0]->newInstance();
            if ($firstAttr->isOption === $isOption) {
                yield $prop => $firstAttr;
            }
        }
    }

    /**
     * TODO: for info in CLI
     *
     * @param list<string> $commandPaths
     * @return \Generator<string, Command>
     */
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
