<?php

namespace App\Module\Core\Command;

use Snidget\CLI\Command;
use Snidget\Database\ConnectConfig;
use Snidget\Database\SQLite\Driver;
use Snidget\Database\SQLite\Meta;

class Database
{
    #[Command('Test database')]
    public function run(): void
    {
        $meta = new Meta(new Driver(new ConnectConfig(
            dsn: 'sqlite:/app/data/example'
        )));
        $meta->getInfo();
    }
}

