<?php

namespace Snidget\Database\SQLite;

use Snidget\Database\AbstractDriver;
use Snidget\Database\ConnectConfig;

class Driver extends AbstractDriver
{
    public function __construct(ConnectConfig $config)
    {
        if ($config->dsn !== 'sqlite::memory:' && !file_exists($config->dsn)) {
            touch(str_replace('sqlite:', '', $config->dsn));
        }
        parent::__construct($config);
    }
}
