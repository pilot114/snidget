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
        $shortOpts = '';
        $longOpts = [];
        $propMap = [];

        foreach (AttributeLoader::getArgs($dtoName) as $prop => $attribute) {
            $name = $prop->getName();
            $type = $prop->getType()->getName();
            $isArray = $type === 'array';
            $isBool = $type === 'bool';
            $short = $attribute->getShort();

            if (!$prop->getType()->allowsNull() && !$isArray && !$isBool) {
                throw new SnidgetException("опция $name должна быть указана как необязательная");
            }

            if ($short) {
                $shortOpts .= $short . ($isBool ? '' : ':');
                $propMap[$short] = $name;
            }

            $longOpts[] = $name . ($isBool ? '' : ':');
            $propMap[$name] = $name;
        }

        $optind = null;
        $options = getopt($shortOpts, $longOpts, $optind);

        foreach (AttributeLoader::getArgs($dtoName) as $prop => $attribute) {
            $name = $prop->getName();
            $type = $prop->getType()->getName();
            $isArray = $type === 'array';
            $isBool = $type === 'bool';
            $short = $attribute->getShort();

            $value = null;
            if (isset($options[$name])) {
                $value = $options[$name];
            } elseif ($short && isset($options[$short])) {
                $value = $options[$short];
            }

            if ($value !== null) {
                if ($isArray) {
                    $data[$name] = is_array($value) ? $value : [$value];
                } elseif ($isBool) {
                    $data[$name] = true;
                } else {
                    $data[$name] = is_array($value) ? end($value) : $value;
                }
            }
        }

        $positionalArgs = array_slice($argv, $optind);
        foreach (AttributeLoader::getArgs($dtoName, false) as $prop => $attribute) {
            $name = $prop->getName();
            $type = $prop->getType()->getName();
            if ($type !== 'array') {
                $data[$name] = array_shift($positionalArgs);
            } else {
                $data[$name] = $positionalArgs;
                $positionalArgs = [];
            }
        }

        return $data;
    }
}
