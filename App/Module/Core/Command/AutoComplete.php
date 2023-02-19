<?php

namespace App\Module\Core\Command;

use App\Module\Core\Schema\Command\AutoCompleteInput;
use Snidget\Attribute\Command;
use Snidget\AttributeLoader;
use Snidget\Schema\Config\AppPaths;

class AutoComplete
{
    #[Command('auto-complete for commands and options')]
    public function run(AutoCompleteInput $data, AppPaths $config): void
    {
        // TODO: partial command input and Exceptions

        // commands
        if (count($data->input) === 1) {
            foreach (AttributeLoader::getCommands($config->getCommandPaths()) as $fqn => $attr) {
                [$class, $method] = explode('::',$fqn);
                $parts = explode('\\', $class);
                $name = end($parts);
                $desc = $attr->getDescription();
                echo "$name:$method\t$desc\n";
            }
            return;
        }


        $dtoName = getCommandInfo($data->input, $config->getCommandPaths())[2];

        // options
        foreach (AttributeLoader::getArgs($dtoName) as $prop => $attribute) {
            $name = $prop->getName();
            $desc = $attribute->getDescription();
            $short = $attribute->getShortcut();
            echo "--$name\t$desc\n";
            if ($short) {
                echo "-$short\t$desc\n";
            }
        }

        // TODO: values for options and args (ENUM ?)
    }
}

