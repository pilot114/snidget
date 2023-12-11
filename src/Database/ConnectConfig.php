<?php

namespace Snidget\Database;

class ConnectConfig
{
    public function __construct(
        public string $dsn = 'sqlite:/app/data/snidget', // sqlite::memory:
        public string $user = '',
        public string $password = '',
    ) {}
}
