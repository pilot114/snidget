<?php

namespace App\Module\Core\Command;

use App\Module\Core\Schema\Command\AutoCompleteInput;
use Snidget\Attribute\Command;
use Snidget\AttributeLoader;
use Snidget\CommandHandler;
use Snidget\Schema\Config\AppPaths;

class AutoComplete
{
    #[Command('auto-complete for commands and options')]
    public function run(AutoCompleteInput $data, AppPaths $config): void
    {
        $count = count($data->input);

        if ($count === 0) {
            return;
        }

        // all commands or filter by part command
        if ($count < 3 && $data->current === 1) {
            foreach (AttributeLoader::getCommands($config->getCommandPaths()) as $fqn => $attr) {
                if ($fqn === __METHOD__) {
                    continue;
                }
                [$class, $method] = explode('::',$fqn);
                $parts = explode('\\', $class);
                $name = end($parts);
                $desc = $attr->getDescription();
                echo "$name:$method\t$desc\n";
            }
            return;
        }

        $handler = new CommandHandler($data->input);
        $info = $handler->getCommandInfo($config->getCommandPaths());
        if ($info === []) {
            return;
        }

        // options
        $dtoName = $info[2];
        foreach (AttributeLoader::getArgs($dtoName) as $prop => $attribute) {
            $name = $prop->getName();
            $desc = $attribute->getDescription();
            $short = $attribute->getShort();
            echo "--$name\t$desc\n";
            if ($short) {
                echo "-$short\t$desc\n";
            }
        }

        // TODO: values for options and args (ENUM ?)
    }
}

