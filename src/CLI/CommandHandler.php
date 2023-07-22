<?php

namespace Snidget\CLI;

use Snidget\Kernel\AttributeLoader;
use Snidget\Kernel\SnidgetException;

class CommandHandler
{
    protected array $paths = [];

    public function __construct(
        protected array $args,
    ) {}

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
        $data = $this->parse($dtoName, $this->args);
        return [$dtoName, $data, $commandClassName, $commandMethodName];
    }

    /** banner and help by commands TODO: to separate command */
    public function default(array $paths): void
    {
        $tmp = [];
        foreach (AttributeLoader::getCommands($paths) as $fqn => $attr) {
            [$class, $method] = explode('::',$fqn);
            $parts = explode('\\', $class);
            $tmp[end($parts)] = [$method => $attr->getDescription()];
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

    protected function parse(string $dtoName, array $argv): array
    {
        $data = [];
        foreach (AttributeLoader::getArgs($dtoName) as $prop => $attribute) {
            $name = $prop->getName();
            // number | string | array. bool equal EXIST
            $type = $prop->getType()->getName();
            $isArray = $type === 'array';
            $isBool = $type === 'bool';
            $short = $attribute->getShort();

            // multi-option
            $shortOptions = [];
            foreach ($argv as $i => $arg) {
                preg_match("#^-([a-z]{2,})$#", $arg, $matches);
                if (count($matches) === 2) {
                    $tmp = array_map(fn($x) => "-$x", str_split($matches[1]));
                    array_push($shortOptions, ...$tmp);
                    unset($argv[$i]);
                }
            }
            array_push($argv, ...$shortOptions);

            // TODO: typing on add to $data
            // TODO: quoted strings

            if (!$prop->getType()->allowsNull() && !$isArray && !$isBool) {
                throw new SnidgetException("опция $name должна быть указана как необязательная");
            }
            foreach ($argv as $i => $arg) {
                preg_match("#^--$name=(.*)$#", $arg, $matches);
                if (count($matches) === 2) {
                    if ($isArray) {
                        $data[$name][] = $matches[1];
                    } else {
                        $data[$name] = $isBool ? true : $matches[1];
                    }
                    unset($argv[$i]);
                    if (!$isArray) {
                        continue 2;
                    }
                }
                if ($arg === "--$name") {
                    if ($isArray) {
                        $data[$name][] = ($argv[$i+1] ?? null);
                    } else {
                        $data[$name] = $isBool ? true : ($argv[$i+1] ?? null);
                    }
                    unset($argv[$i], $argv[$i+1]);
                    if (!$isArray) {
                        continue 2;
                    }
                }
            }

            if ($short) {
                foreach ($argv as $i => $arg) {
                    preg_match("#^-$short=(.*)$#", $arg, $matches);
                    if (count($matches) === 2) {
                        if ($isArray) {
                            $data[$name][] = $matches[1];
                        } else {
                            $data[$name] = $isBool ? true : $matches[1];
                        }
                        unset($argv[$i]);
                        if (!$isArray) {
                            continue 2;
                        }
                    }
                    if ($arg === "-$short") {
                        if ($isArray) {
                            $data[$name][] = ($argv[$i+1] ?? null);
                        } else {
                            $data[$name] = $isBool ? true : ($argv[$i+1] ?? null);
                        }
                        unset($argv[$i]);
                        if (!$isBool) {
                            unset($argv[$i+1]);
                        }
                        if (!$isArray) {
                            continue 2;
                        }
                    }
                }
            }
        }
        foreach (AttributeLoader::getArgs($dtoName, false) as $prop => $attribute) {
            $name = $prop->getName();
            $type = $prop->getType()->getName();
            if ($type !== 'array') {
                $data[$name] = array_shift($argv);
            }
            if ($type === 'array') {
                $data[$name] = $argv;
                $argv = [];
            }
        }
        return $data;
    }
}
