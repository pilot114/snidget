<?php

namespace App\Module\Core\Command;

use App\Module\Core\Schema\Command\TestInput;
use Snidget\CLI\Command;

class Test
{
    #[Command('Creates a new user')]
    public function run(TestInput $data): void
    {
        echo "success!\n";
        dump($data);
    }
}

