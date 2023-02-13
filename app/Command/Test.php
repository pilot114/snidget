<?php

namespace App\Command;

use App\Schema\Command\TestInput;
use Snidget\Attribute\Command;

class Test
{
    #[Command('Creates a new user')]
    public function run(TestInput $data): void
    {
        echo "sucess!\n";
        dump($data);
    }
}

