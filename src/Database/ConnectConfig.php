<?php

declare(strict_types=1);

namespace Snidget\Database;

class ConnectConfig
{
    public function __construct(
        public readonly string $dsn = 'sqlite:/app/data/snidget', // sqlite::memory:
        public readonly string $user = '',
        public readonly string $password = '',
    ) {}
}
