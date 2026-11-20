<?php

namespace Snidget\CLI;

use ReflectionNamedType;
use ReflectionProperty;
use Snidget\Kernel\AttributeLoader;
use Snidget\Kernel\Schema\Type;
use Snidget\Kernel\SnidgetException;

class CommandHandler
{
    /**
     * @param list<string> $args
     */
    public function __construct(
        protected array $args,
    ) {
    }

    /**
     * DTO, данные для него, класс и метод команды
     *
     * @param list<string> $paths
     * @return array{}|array{class-string<Type>|null, array<string, mixed>|null, string, string}
     * @throws SnidgetException
     */
    public function extractCommand(array $paths): array
    {
        if (count($this->args) === 1) {
            return [];
        }

        $data = $this->getCommandInfo($paths);
        if ($data === []) {
            return [];
        }

        [$commandClassName, $commandMethodName, $dtoName] = $data;
        if ($dtoName === null) {
            return [null, null, $commandClassName, $commandMethodName];
        }

        $data = $this->parse($dtoName, $this->args);
        return [$dtoName, $data, $commandClassName, $commandMethodName];
    }

    /**
     * banner and help by commands TODO: to separate command
     *
     * @param list<string> $paths
     */
    public function default(array $paths): void
    {
        $tmp = [];
        foreach (AttributeLoader::getCommands($paths) as $fqn => $attr) {
            [$class, $method] = explode('::', $fqn);
            $parts = explode('\\', $class);
            $tmp[end($parts)][$method] = $attr->getDescription();
        }

        foreach ($tmp as $groupName => $group) {
            echo "$groupName\n";
            foreach ($group as $command => $desc) {
                echo "    $command\t$desc\n";
            }
        }
    }

    /**
     * @param list<string> $paths
     * @return array{}|array{string, string, class-string<Type>|null}
     */
    public function getCommandInfo(array $paths): array
    {
        array_shift($this->args);
        $command = array_shift($this->args);
        if ($command === null || !str_contains($command, ':')) {
            return [];
        }

        [$command, $subCommand] = explode(':', $command);
        return AttributeLoader::getDtoInfoByCommandName($paths, $command, $subCommand);
    }

    /**
     * @param list<string> $argv
     * @return array<string, mixed>
     */
    protected function parse(string $dtoName, array $argv): array
    {
        /** @var array<string, string> $types */
        $types = [];
        $shorts = [];
        foreach (AttributeLoader::getArgs($dtoName) as $prop => $attribute) {
            $name = $prop->getName();
            $type = self::typeName($prop);
            if ($prop->getType()?->allowsNull() === false && $type !== 'array' && $type !== 'bool') {
                throw new SnidgetException("опция $name должна быть указана как необязательная");
            }

            $types[$name] = $type;
            if ($attribute->short !== null) {
                $shorts[$attribute->short] = $name;
            }
        }

        // getopt() не подходит: читает глобальный argv и останавливается на имени команды
        $data = [];
        /** @var array<string, list<string>> $lists значения опций-массивов */
        $lists = [];
        $positionalArgs = [];
        while (($arg = array_shift($argv)) !== null) {
            if ($arg === '--') {
                array_push($positionalArgs, ...$argv);
                break;
            }

            if (str_starts_with($arg, '--')) {
                $parts = explode('=', substr($arg, 2), 2);
                $name = $parts[0];
                $value = $parts[1] ?? null;
            } elseif (strlen($arg) > 1 && $arg[0] === '-') {
                $name = $shorts[$arg[1]] ?? throw new SnidgetException("Неизвестная опция $arg");
                $rest = substr($arg, 2);
                $value = null;
                if ($types[$name] === 'bool') {
                    // -de — несколько флагов сразу
                    if ($rest !== '') {
                        array_unshift($argv, '-' . $rest);
                    }
                } elseif ($rest !== '') {
                    $value = ltrim($rest, '=');
                }
            } else {
                $positionalArgs[] = $arg;
                continue;
            }

            $type = $types[$name] ?? throw new SnidgetException("Неизвестная опция $arg");
            if ($type === 'bool') {
                $data[$name] = true;
                continue;
            }

            $value ??= array_shift($argv) ?? throw new SnidgetException("Не указано значение опции $arg");
            if ($type === 'array') {
                $lists[$name][] = $value;
            } else {
                $data[$name] = $this->cast($value, $type);
            }
        }

        $data = [...$data, ...$lists];
        foreach (AttributeLoader::getArgs($dtoName, false) as $prop => $attribute) {
            $name = $prop->getName();
            $type = self::typeName($prop);
            if ($type !== 'array') {
                $value = array_shift($positionalArgs);
                $data[$name] = $value === null ? null : $this->cast($value, $type);
            } else {
                $data[$name] = $positionalArgs;
                $positionalArgs = [];
            }
        }

        return $data;
    }

    protected function cast(string $value, string $type): mixed
    {
        return match ($type) {
            'int'   => (int)$value,
            'float' => (float)$value,
            default => $value,
        };
    }

    protected static function typeName(ReflectionProperty $prop): string
    {
        $type = $prop->getType();
        return $type instanceof ReflectionNamedType ? $type->getName() : 'mixed';
    }
}
