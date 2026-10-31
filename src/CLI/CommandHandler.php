<?php

namespace Snidget\CLI;

use Snidget\Kernel\AttributeLoader;
use Snidget\Kernel\SnidgetException;

class CommandHandler
{
    protected array $paths = [];

    public function __construct(
        protected array $args,
    ) {
    }

    /**
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

    /** banner and help by commands TODO: to separate command */
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

    public function getCommandInfo(array $paths): array
    {
        array_shift($this->args);
        $command = array_shift($this->args);
        if (!$command) {
            return [];
        }

        if (!str_contains($command, ':')) {
            return [];
        }

        [$command, $subCommand] = explode(':', $command);
        return AttributeLoader::getDtoInfoByCommandName($paths, $command, $subCommand);
    }

    /**
     * @param list<string> $argv
     */
    protected function parse(string $dtoName, array $argv): array
    {
        /** @var array<string, string> $types */
        $types = [];
        $shorts = [];
        foreach (AttributeLoader::getArgs($dtoName) as $prop => $attribute) {
            $name = $prop->getName();
            $type = $prop->getType()->getName();
            if (!$prop->getType()->allowsNull() && $type !== 'array' && $type !== 'bool') {
                throw new SnidgetException("опция $name должна быть указана как необязательная");
            }

            $types[$name] = $type;
            if ($attribute->short) {
                $shorts[$attribute->short] = $name;
            }
        }

        // getopt() не подходит: читает глобальный argv и останавливается на имени команды
        $data = [];
        $positionalArgs = [];
        while (($arg = array_shift($argv)) !== null) {
            if ($arg === '--') {
                array_push($positionalArgs, ...$argv);
                break;
            }

            if (str_starts_with($arg, '--')) {
                [$name, $value] = array_pad(explode('=', substr($arg, 2), 2), 2, null);
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
                $data[$name][] = $value;
            } else {
                $data[$name] = $this->cast($value, $type);
            }
        }

        foreach (AttributeLoader::getArgs($dtoName, false) as $prop => $attribute) {
            $name = $prop->getName();
            $type = $prop->getType()->getName();
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
}
